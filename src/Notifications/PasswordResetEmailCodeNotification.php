<?php

namespace Mortezamasumi\FbAuth\Notifications;

use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\ResetPassword as BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class PasswordResetEmailCodeNotification extends ResetPassword
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
            ->subject(__('fb-auth::fb-auth.reset-password.mail-message.subject'))
            ->greeting(__('fb-auth::fb-auth.reset-password.mail-message.greeting'))
            ->line(__('fb-auth::fb-auth.reset-password.mail-message.line1'))
            ->line(__('fb-auth::fb-auth.reset-password.mail-message.line2'))
            ->line(new HtmlString('<p style="font-size: 2rem; line-height: 2.5rem; font-weight: 800; text-align: center; color: black; letter-spacing: 8px;">'.$this->code.'</p>'))
            ->line(__('fb-auth::fb-auth.reset-password.mail-message.timeout', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('fb-auth::fb-auth.reset-password.mail-message.ending'))
            ->salutation(new HtmlString(__('fb-auth::fb-auth.reset-password.mail-message.salutation', [
                'name' => __(config('app.name'))
            ])));
    }
}
