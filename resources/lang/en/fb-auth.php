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
    'otp' => [
        'verify_mobile_title' => 'Account verification by mobile',
        'verify_code_title' => 'Account verification by email',
        'resend_action' => 'Resend code',
        'validation' => 'Code is not correct',
        'expired' => 'Code has been expired, request to resend',
        'mobile_label' => 'Enter the code sent to your number',
        'code_label' => 'Enter the code sent to your email',
        'prompt_action' => 'Verify',
    ],
    'reset_password' => [
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
        'text_message' => ':app, Password reset code: :code',
        'mail_message' => [
            'subject' => 'Reset Password Notification',
            'greeting' => 'Hello!',
            'line1' => 'You are receiving this email containing code because we received a password reset request for your account.',
            'line2' => 'Please enter the follwing code into the reset password page.',
            'action' => 'Reset Password',
            'timeout' => 'This code will expire in :count minutes.',
            'ending' => 'If you did not request a password reset, no further action is required.',
            'salutation' => 'Regards,<br>:name',
        ],
    ],
    'verify' => [
        'prompt' => [
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
                'label' => 'Verify',
            ]
        ],
        'text_message' => ':app, Password reset code: :code',
        'mail_message' => [
            'subject' => 'Verify Email Address',
            'greeting' => 'Hello!',
            'line1' => 'You are receiving this email containing code because there is an account registered with this email.',
            'line2' => 'Please enter the follwing code into the verification page.',
            'action' => 'Verify Email Address',
            'timeout' => 'This code will expire in :count minutes.',
            'ending' => 'If you did not create an account, no further action is required.',
            'salutation' => 'Regards,<br>:name',
        ],
    ],
];
