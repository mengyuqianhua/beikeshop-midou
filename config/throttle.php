<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 接口限流配置
    |--------------------------------------------------------------------------
    | 所有数值均支持通过 .env 覆盖，修改后执行 php artisan config:clear
    | 数值为 0 时会拦截全部请求，请谨慎设置
    */

    'api' => [
        'per_minute' => (int) env('THROTTLE_API_PER_MINUTE', 600),
    ],

    'shop' => [
        'per_minute' => (int) env('THROTTLE_SHOP_PER_MINUTE', 300),
    ],

    'login' => [
        'email_per_minute'  => (int) env('THROTTLE_LOGIN_EMAIL_PER_MINUTE', 5),
        'client_per_minute' => (int) env('THROTTLE_LOGIN_CLIENT_PER_MINUTE', 20),
    ],

    'register' => [
        'per_minute' => (int) env('THROTTLE_REGISTER_PER_MINUTE', 5),
    ],

    'captcha' => [
        'per_minute' => (int) env('THROTTLE_CAPTCHA_PER_MINUTE', 60),
    ],

    'register_send' => [
        'email_per_minute' => (int) env('THROTTLE_REGISTER_SEND_EMAIL_PER_MINUTE', 1),
        'client_per_hour'  => (int) env('THROTTLE_REGISTER_SEND_CLIENT_PER_HOUR', 5),
    ],

    'forgot_send' => [
        'email_per_minute' => (int) env('THROTTLE_FORGOT_SEND_EMAIL_PER_MINUTE', 1),
        'client_per_hour'  => (int) env('THROTTLE_FORGOT_SEND_CLIENT_PER_HOUR', 5),
    ],

    'forgot_verify' => [
        'email_per_minute'  => (int) env('THROTTLE_FORGOT_VERIFY_EMAIL_PER_MINUTE', 5),
        'client_per_minute' => (int) env('THROTTLE_FORGOT_VERIFY_CLIENT_PER_MINUTE', 10),
    ],

    'upload' => [
        'per_minute' => (int) env('THROTTLE_UPLOAD_PER_MINUTE', 20),
    ],

    'checkout' => [
        'per_minute' => (int) env('THROTTLE_CHECKOUT_PER_MINUTE', 3),
    ],
];
