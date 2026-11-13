<?php

namespace Beike\Mail;

use Illuminate\Mail\Mailable;

class CustomerRegisterCode extends Mailable
{
    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function build()
    {
        return $this->subject(trans('mail.register_code_title'))->view('mails.register_code', ['code' => $this->code]);
    }
}
