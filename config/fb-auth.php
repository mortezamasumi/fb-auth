<?php

return [
    'auth_type' => env('AUTH_TYPE', 'link'),
    'otp_digits' => env('OTP_DIGITS', 4),
    'otp_expiration' => env('OTP_EXPIRATION', 120),
];
