<?php

namespace Beike\Notifications;

use Beike\Channels\BkMailChannel;
use Beike\Mail\CustomerRegisterCode;
use Illuminate\Notifications\Notification;

class RegisterCodeNotification extends Notification
{
    private string $code;

    private string $email;

    public function __construct(string $code, string $email)
    {
        $this->code  = $code;
        $this->email = $email;
    }

    /**
     * 注册验证码仅通过邮件发送
     */
    public function via($notifiable)
    {
        return system_setting('base.mail_engine') ? [BkMailChannel::class] : [];
    }

    public function toMail($notifiable)
    {
        return (new CustomerRegisterCode($this->code))->to($this->email ?: $notifiable->email);
    }
}
