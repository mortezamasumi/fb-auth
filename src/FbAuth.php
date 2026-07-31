<?php

namespace Mortezamasumi\FbAuth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Mortezamasumi\FbAuth\Enums\AuthType;

class FbAuth
{
    public function encodeEmail(string $email): string
    {
        [$username, $domain] = explode('@', $email);
        $maskedUsername = str_repeat('*', 2).substr($username, -2, 2);
        $domainParts = explode('.', $domain);
        $maskedDomain = str_repeat('*', 2).substr($domainParts[0], -2, 2);
        $codedEmail = $maskedUsername.'@'.$maskedDomain.'.'.$domainParts[1];

        return $codedEmail;
    }

    public function generateRandomCode(): string
    {
        $digits = config('fb-auth.otp_digits');
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;

        return str_pad((string) random_int($min, $max), $digits, '0', STR_PAD_LEFT);
    }

    public function createCode(Model $user): string
    {
        $code = $this->generateRandomCode();

        $identifier = $this->resolveIdentifier($user);

        Cache::forget('otp-'.$identifier);

        Cache::add(
            'otp-'.$identifier,
            [$code, now()],
            (int) config('fb-auth.otp_expiration')
        );

        return $code;
    }

    protected function resolveIdentifier(Model $user): string
    {
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        return match ($authType) {
            AuthType::Mobile => (string) $user->getAttribute('mobile'),
            AuthType::Code => (string) $user->getAttribute('email'),
            default => (string) $user->getKey(),
        };
    }

    /**
     * @return array{title: string, body: string}
     */
    public function getResetPasswordNotificationKeys(): array
    {
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        return match ($authType) {
            AuthType::Mobile => [
                'title' => 'fb-auth::fb-auth.reset_password.request.notification.mobile.title',
                'body' => 'fb-auth::fb-auth.reset_password.request.notification.mobile.body',
            ],
            default => [
                'title' => 'fb-auth::fb-auth.reset_password.request.notification.code.title',
                'body' => 'fb-auth::fb-auth.reset_password.request.notification.code.body',
            ],
        };
    }
}
