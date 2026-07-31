<?php

namespace Mortezamasumi\FbAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class VerifyMobileNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?string $url = null;

    public function __construct(
        protected string $code,
    ) {}

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    public function toSms(object $notifiable): string
    {
        return __('fb-auth::fb-auth.verify.text_message', [
            'app' => __(config('app.name')),
            'code' => $this->code,
        ]);
    }
}
