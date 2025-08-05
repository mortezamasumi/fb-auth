<?php

namespace Mortezamasumi\FbAuth\Notifications;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class PasswordResetCodeNotification extends ResetPassword
{
    protected string $code;

    public function __construct($token, $code)
    {
        $this->token = $token;
        $this->code = $code;
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(__('fb-auth::fb-auth.reset_password.mail_message.subject'))
            ->greeting(__('fb-auth::fb-auth.reset_password.mail_message.greeting'))
            ->line(__('fb-auth::fb-auth.reset_password.mail_message.line1'))
            ->line(__('fb-auth::fb-auth.reset_password.mail_message.line2'))
            ->line(new HtmlString('<p style="font-size: 2rem; line-height: 2.5rem; font-weight: 800; text-align: center; color: black; letter-spacing: 8px;">'.$this->code.'</p>'))
            ->line(__('fb-auth::fb-auth.reset_password.mail_message.timeout', ['count' => (int) (config('fb-auth.otp_expiration') / 60)]))
            ->line(__('fb-auth::fb-auth.reset_password.mail_message.ending'))
            ->salutation(new HtmlString(__('fb-auth::fb-auth.reset_password.mail_message.salutation', [
                'name' => __(config('app.name'))
            ])));
    }
}
