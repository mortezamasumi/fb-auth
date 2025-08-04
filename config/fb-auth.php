<?php

return [
    'mobile_required' => true,
    'email_required' => false,
    // 'mobile_required' => false,
    // 'email_required' => true,
    'username_required' => false,
    'email_link_verification' => false,
    'otp_digits' => env('OTP_DIGITS', 4),
    'otp_expiration' => env('OTP_EXPIRATION', 120),
];
