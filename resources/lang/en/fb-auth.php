<?php

return [
    'gender' => [
        'female' => 'Female',
        'male' => 'Male',
        'undefined' => 'Undefined',
        'ms' => 'Ms.',
        'mr' => 'Mr.',
        'girl' => 'Girl',
        'boy' => 'Boy',
    ],
    'marriage' => [
        'single' => 'Single',
        'married' => 'Married',
        'unknown' => 'Unknown',
    ],
    'form' => [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'nid' => 'National ID number',
        'nid_pass' => 'National ID/Passport number',
        'gender' => 'Gender',
        'birth_date' => 'Birth date',
        'mobile' => 'Mobile',
        'username' => 'Username',
        'roles' => 'Roles',
        'user_profile' => 'Profile',
        'account' => 'Account',
        'password' => 'Password',
        'expired_at' => 'Account expire at',
        'force_change_password' => 'Force change password',
        'profile' => [
            'father_name' => 'Father name',
        ],
    ],
    'notification' => [
        'title' => 'Profile updated successfully',
    ],
    'reset-password' => [
        'otp-validation' => 'Code is not correct',
        'otp-expired' => 'Code is expired, request to resend',
        'resend-code' => 'Resend code',
        'request' => [
            'notification' => [
                'mobile' => [
                    'title' => 'SMS sent to given number',
                    'body' => 'Password reset code sent by sms',
                ],
                'code' => [
                    'title' => 'Email sent to given email',
                    'body' => 'Password reset code sent by email',
                ],
            ],
            'action' => [
                'email' => 'Send email',
                'mobile' => 'Send sms',
            ]
        ],
        'text-message' => ':app, Password reset code: :code',
        'mail-message' => [
            'subject' => 'Reset Password Notification',
            'greeting' => 'Hello!',
            'line1' => 'You are receiving this email containing code because we received a password reset request for your account.',
            'line2' => 'Please enter the follwing code into the reset password page.',
            'action' => 'Reset Password',
            'timeout' => 'This code will expire in :count minutes.',
            'ending' => 'If you did not request a password reset, no further action is required.',
            'salutation' => 'ًRegards,<br>:name',
        ],
    ],
];
