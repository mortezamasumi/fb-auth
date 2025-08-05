<?php

return [
    'mobile_required' => true,
    'email_required' => false,
    'username_required' => false,
    'email_link_verification' => false,
    'otp_digits' => env('OTP_DIGITS', 4),
    'otp_expiration' => env('OTP_EXPIRATION', 120),
];
