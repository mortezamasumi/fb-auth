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

        // return '1234';
        return str_pad(random_int($min, $max), $digits, '0', STR_PAD_LEFT);
    }

    public function createCode(Model $user): string
    {
        $code = $this->generateRandomCode();

        $identifire = match (config('app.auth_type')) {
            AuthType::Mobile => $user->mobile,
            AuthType::Code => $user->email,
            default => $user->id,
        };

        Cache::forget('otp-'.$identifire);

        Cache::add(
            'otp-'.$identifire,
            [$code, now()],
            (int) config('fb-auth.otp_expiration')
        );

        return $code;
    }
}
