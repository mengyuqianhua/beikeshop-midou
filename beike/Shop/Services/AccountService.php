<?php

/**
 * CartService.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-01-05 10:12:57
 * @modified   2022-01-05 10:12:57
 */

namespace Beike\Shop\Services;

use Beike\Models\Customer;
use Beike\Notifications\RegisterCodeNotification;
use Beike\Repositories\CustomerRepo;
use Beike\Repositories\VerifyCodeRepo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountService
{
    /**
     * 注册用户
     *
     * @param array $data // ['email', 'password']
     * @return mixed
     */
    public static function register(array $data)
    {
        $customerApproved            = system_setting('base.customer_approved', 0); // approve_customer为是否需要审核客户

        $data['customer_group_id'] = system_setting('base.default_customer_group_id', 0); // default_customer_group_id为默认客户组名称
        $data['from']              = $data['from'] ?? 'pc';
        $data['locale']            = locale();

        if ($data['email'] ?? '') {
            $data['name'] = substr($data['email'], 0, strrpos($data['email'], '@'));
        }
        $data['avatar'] = $data['avatar'] ?? '';
        $data['active'] = 1;

        if ($customerApproved) {
            $data['status'] = 'pending';
        } else {
            $data['status'] = 'approved';
        }

        hook_action('service.account.register.before', $data);

        $customer = CustomerRepo::create($data);
        $customer->notifyRegistration();

        hook_action('service.account.register.after', $customer);

        return $customer;
    }

    /**
     * 发送注册邮箱验证码
     */
    public static function sendRegisterCode(string $email): void
    {
        if (! system_setting('base.mail_engine')) {
            throw new \Exception(trans('shop/login.mail_not_configured'));
        }

        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $account = self::registerCodeAccount($email);

        VerifyCodeRepo::deleteByAccount($account);
        Cache::forget(self::registerAttemptsKey($email));
        VerifyCodeRepo::create([
            'account' => $account,
            'code'    => $code,
        ]);

        (new Customer(['email' => $email]))->notifyNow(new RegisterCodeNotification($code, $email));
    }

    /**
     * 校验注册邮箱验证码
     */
    public static function verifyRegisterCode(string $email, string $code): void
    {
        $account     = self::registerCodeAccount($email);
        $attemptsKey = self::registerAttemptsKey($email);
        $verifyCode  = VerifyCodeRepo::findByAccount($account);

        if (empty($verifyCode)) {
            throw new \Exception(trans('shop/login.register_code_expired'));
        }

        if ($verifyCode->created_at->addMinutes(10) < Carbon::now()) {
            $verifyCode->delete();

            throw new \Exception(trans('shop/login.register_code_expired'));
        }

        if ((int) Cache::get($attemptsKey, 0) >= 5) {
            $verifyCode->delete();
            Cache::forget($attemptsKey);

            throw new \Exception(trans('shop/login.register_code_too_many_attempts'));
        }

        if (! hash_equals((string) $verifyCode->code, (string) $code)) {
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(10));

            if ($attempts >= 5) {
                $verifyCode->delete();
                Cache::forget($attemptsKey);

                throw new \Exception(trans('shop/login.register_code_too_many_attempts'));
            }

            throw new \Exception(trans('shop/login.register_code_error'));
        }

        $verifyCode->delete();
        Cache::forget($attemptsKey);
    }

    private static function registerCodeAccount(string $email): string
    {
        return 'register:' . strtolower($email);
    }

    private static function registerAttemptsKey(string $email): string
    {
        return 'register:code:attempts:' . sha1(strtolower($email));
    }

    /**
     * 发送验证码通过$type方式，type为email或telephone
     * @param $email
     * @param $type
     * @return void
     */
    public static function sendVerifyCodeForForgotten($email, $type)
    {
        $code = str_pad(mt_rand(10, 999999), 6, '0', STR_PAD_LEFT);

        VerifyCodeRepo::deleteByAccount($email);
        Cache::forget('forgot:code:attempts:' . sha1(strtolower((string) $email)));
        VerifyCodeRepo::create([
            'account' => $email,
            'code'    => $code,
        ]);

        Log::info("找回密码验证码：{$code}");

        Customer::query()->where('email', $email)->firstOrFail()->notifyVerifyCodeForForgotten($code);
    }

    /**
     * 验证验证码是否正确，并修改密码为新密码
     * @param        $code
     * @param        $account
     * @param        $password
     * @param string $type     $account类型，email代表$account为邮箱地址，telephone代表$account为手机号码
     * @return void
     * @throws \Exception
     */
    public static function verifyAndChangePassword($code, $account, $password, string $type = 'email')
    {
        $attemptsKey = 'forgot:code:attempts:' . sha1(strtolower((string) $account));
        $verifyCode  = VerifyCodeRepo::findByAccount($account);

        if (empty($verifyCode)) {
            throw new \Exception(trans('shop/account.verify_code_expired'));
        }

        if ($verifyCode->created_at->addMinutes(10) < Carbon::now()) {
            $verifyCode->delete();
            Cache::forget($attemptsKey);

            throw new \Exception(trans('shop/account.verify_code_expired'));
        }

        // 同一验证码最多允许 5 次错误尝试，超过后作废
        if ((int) Cache::get($attemptsKey, 0) >= 5) {
            $verifyCode->delete();
            Cache::forget($attemptsKey);

            throw new \Exception(trans('shop/account.verify_code_too_many_attempts'));
        }

        if ($verifyCode->code != $code) {
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, now()->addMinutes(10));

            if ($attempts >= 5) {
                $verifyCode->delete();
                Cache::forget($attemptsKey);

                throw new \Exception(trans('shop/account.verify_code_too_many_attempts'));
            }

            throw new \Exception(trans('shop/account.verify_code_error'));
        }

        Cache::forget($attemptsKey);

        if ($type == 'email') {
            $customer = CustomerRepo::findByEmail($account);
            if (! $customer) {
                throw new \Exception(trans('shop/account.account_not_exist'));
            }
        } elseif ($type == 'telephone') {
            throw new \Exception('暂不支持手机号码找回密码');
        } else {
            throw new \Exception('找回密码类型错误');
        }
        CustomerRepo::update($customer, ['password' => $password]);
        $verifyCode->delete();
    }
}
