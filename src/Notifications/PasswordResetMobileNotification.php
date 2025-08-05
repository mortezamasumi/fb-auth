<?php

namespace Mortezamasumi\FbAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasswordResetMobileNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $url;

    public function __construct(
        protected string $code,
    ) {}

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms(object $notifiable): string
    {
        return __('fb-auth::fb-auth.reset_password.text_message', [
            'app' => __(config('app.name')),
            'code' => $this->code,
        ]);
    }
}
