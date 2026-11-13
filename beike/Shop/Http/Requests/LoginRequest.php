<?php

namespace Beike\Shop\Http\Requests;

use Beike\Shop\Services\CaptchaService;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email'    => 'required|email:rfc',
            'password' => 'required',
            'captcha'  => ['required', function ($attribute, $value, $fail) {
                if (! CaptchaService::verify($value, (string) $this->input('captcha_token'))) {
                    $fail(trans('shop/login.captcha_error'));
                }
            }],
        ];
    }

    public function attributes()
    {
        return [
            'email'    => trans('shop/login.email_address'),
            'password' => trans('shop/login.password'),
            'captcha'  => trans('shop/login.captcha'),
        ];
    }
}
