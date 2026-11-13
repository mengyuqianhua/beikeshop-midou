<?php

namespace Beike\Shop\Http\Controllers;

use Beike\Shop\Services\CaptchaService;
use Illuminate\Http\Request;

class CaptchaController extends Controller
{
    /**
     * 输出图形验证码
     */
    public function image(Request $request)
    {
        return CaptchaService::render(CaptchaService::generate((string) $request->query('token', '')));
    }
}
