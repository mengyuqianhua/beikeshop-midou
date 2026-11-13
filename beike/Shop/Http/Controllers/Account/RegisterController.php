<?php

/**
 * LoginController.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-06-22 20:22:54
 * @modified   2022-06-22 20:22:54
 */

namespace Beike\Shop\Http\Controllers\Account;

use Beike\Models\Customer;
use Beike\Repositories\CartRepo;
use Beike\Shop\Http\Controllers\Controller;
use Beike\Shop\Http\Requests\RegisterCodeRequest;
use Beike\Shop\Http\Requests\RegisterRequest;
use Beike\Shop\Services\AccountService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function index()
    {
        return redirect(shop_route('login.index'));
    }

    public function sendVerifyCode(RegisterCodeRequest $request): JsonResponse
    {
        try {
            AccountService::sendRegisterCode((string) $request->input('email'));
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }

        return json_success(trans('shop/login.register_code_sent'));
    }

    public function store(RegisterRequest $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            AccountService::verifyRegisterCode((string) $credentials['email'], (string) $request->input('code'));
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }

        $customer               = AccountService::register($credentials);
        $guestCartProduct       = CartRepo::allCartProducts(0);
        auth(Customer::AUTH_GUARD)->attempt($credentials);
        CartRepo::mergeGuestCart($customer, $guestCartProduct);

        if ($customer->status == 'approved') {
            return json_success(trans('shop/login.register_success'));
        }

        return json_fail(trans('shop/login.should_be_approved'));

    }
}
