<?php

namespace Beike\Shop\Http\Requests;

use Beike\Models\Customer;
use Beike\Shop\Services\CaptchaService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'   => [
                'required',
                'email:rfc',
                function ($attribute, $value, $fail) {
                    if (Customer::where('email', $value)->withoutTrashed()->exists()) {
                        $fail(trans('shop/login.email_address_error', ['email' => $value]));
                    } elseif (Customer::withTrashed()->where('email', $value)->onlyTrashed()->exists()) {
                        $fail(trans('shop/login.email_address_deleted'));
                    }
                },
            ],
            'captcha' => ['required', function ($attribute, $value, $fail) {
                if (! CaptchaService::verify($value, (string) $this->input('captcha_token'))) {
                    $fail(trans('shop/login.captcha_error'));
                }
            }],
        ];
    }

    public function attributes()
    {
        return [
            'email'   => trans('shop/login.email_address'),
            'captcha' => trans('shop/login.captcha'),
        ];
    }
}
