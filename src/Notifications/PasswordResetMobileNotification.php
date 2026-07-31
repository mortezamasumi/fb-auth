<?php

namespace Mortezamasumi\FbAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasswordResetMobileNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?string $url = null;

    public function __construct(
        protected string $code,
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
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
