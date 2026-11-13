@extends('layout.mail')

@section('content')
  <tbody>
    <tr style="font-weight:300">
      <td style="width:3.2%;max-width:30px;"></td>
      <td style="max-width:480px;text-align:left;">
        <h1 style="font-size: 20px; line-height: 36px; margin: 0px 0px 22px;">
          {{ __('mail.register_code_title') }}
        </h1>
        <p style="line-height: 24px; margin: 6px 0px 0px; overflow-wrap: break-word; word-break: break-all;">
          <span style="color: rgb(51, 51, 51); font-size: 14px;">{{ __('mail.register_code_text') }}</span>
        </p>
        <p style="line-height: 24px; margin: 18px 0px 0px; overflow-wrap: break-word; word-break: break-all;">
          <span style="color: rgb(51, 51, 51); font-size: 14px;">{{ __('shop/login.email_code') }}:
            <span style="font-weight: bold; font-size: 20px; letter-spacing: 4px;">{{ $code }}</span>
          </span>
        </p>
        <p style="font-size: 12px; color: #999; line-height: 22px; margin-top: 24px;">
          {{ __('mail.register_code_ignore') }}
        </p>
      </td>
      <td style="width:3.2%;max-width:30px;"></td>
    </tr>
  </tbody>
@endsection
