<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('throttle.api.per_minute', 600))->by(optional($request->user())->id ?: $request->ip());
        });

        $tooManyRequests = function ($request = null, array $headers = []) {
            return json_fail(trans('shop/common.too_many_requests'), [], 429)->withHeaders($headers);
        };

        // 限流标识：登录客户按客户，游客按会话，并附带 IP 作为兜底
        $shopKey = function (Request $request) {
            $customer = current_customer();
            $identity = $customer ? 'customer:' . $customer->id : 'session:' . get_session_id();

            return $identity . '|ip:' . $request->ip();
        };

        // 商城全站基础限流
        RateLimiter::for('shop', function (Request $request) use ($tooManyRequests, $shopKey) {
            return Limit::perMinute((int) config('throttle.shop.per_minute', 300))->by($shopKey($request))->response($tooManyRequests);
        });

        // 登录：邮箱维度限制撞库，客户端维度限制高频尝试
        RateLimiter::for('login', function (Request $request) use ($tooManyRequests, $shopKey) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('throttle.login.email_per_minute', 5))->by('login:email:' . $email)->response($tooManyRequests),
                Limit::perMinute((int) config('throttle.login.client_per_minute', 20))->by('login:client:' . $shopKey($request))->response($tooManyRequests),
            ];
        });

        // 注册：按客户端标识限制批量注册
        RateLimiter::for('register', function (Request $request) use ($tooManyRequests, $shopKey) {
            return Limit::perMinute((int) config('throttle.register.per_minute', 5))->by($shopKey($request))->response($tooManyRequests);
        });

        // 图形验证码：限制高频刷新
        RateLimiter::for('captcha', function (Request $request) use ($tooManyRequests, $shopKey) {
            return Limit::perMinute((int) config('throttle.captcha.per_minute', 60))->by($shopKey($request))->response($tooManyRequests);
        });

        // 注册发码：邮箱维度限制发送频率，客户端维度限制发送总量
        RateLimiter::for('register_send', function (Request $request) use ($tooManyRequests, $shopKey) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('throttle.register_send.email_per_minute', 1))->by('register:email:' . $email)->response($tooManyRequests),
                Limit::perHour((int) config('throttle.register_send.client_per_hour', 5))->by('register:client:' . $shopKey($request))->response($tooManyRequests),
            ];
        });

        // 找回密码发码：邮箱维度限制发送频率，客户端维度限制发送总量
        RateLimiter::for('forgot_send', function (Request $request) use ($tooManyRequests, $shopKey) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('throttle.forgot_send.email_per_minute', 1))->by('email:' . $email)->response($tooManyRequests),
                Limit::perHour((int) config('throttle.forgot_send.client_per_hour', 5))->by($shopKey($request))->response($tooManyRequests),
            ];
        });

        // 找回密码校验：邮箱维度限制验证码猜测，客户端维度限制高频请求
        RateLimiter::for('forgot_verify', function (Request $request) use ($tooManyRequests, $shopKey) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute((int) config('throttle.forgot_verify.email_per_minute', 5))->by('forgot:email:' . $email)->response($tooManyRequests),
                Limit::perMinute((int) config('throttle.forgot_verify.client_per_minute', 10))->by('forgot:client:' . $shopKey($request))->response($tooManyRequests),
            ];
        });

        // 前台文件上传：按客户端标识限制磁盘滥用
        RateLimiter::for('upload', function (Request $request) use ($tooManyRequests, $shopKey) {
            return Limit::perMinute((int) config('throttle.upload.per_minute', 20))->by($shopKey($request))->response($tooManyRequests);
        });

        // 提交订单：按客户端标识限制重复提交
        RateLimiter::for('checkout', function (Request $request) use ($tooManyRequests, $shopKey) {
            return Limit::perMinute((int) config('throttle.checkout.per_minute', 3))->by($shopKey($request))->response($tooManyRequests);
        });
    }
}
