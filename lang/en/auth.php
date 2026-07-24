<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'registration' => [
        'success' => 'Registered Successfully',
    ],
    'login' => [
        'success' => 'Login Successfully',
    ],
    'logout' => [
        'success' => 'Logout Successfully',
    ],
    'otp' => [
        'sent' => 'Verification code sent to your email',
        'invalid_code' => 'Invalid verification code',
        'expired' => 'Verification code has expired, please request a new one',
        'too_many_attempts' => 'Too many requests, please try again later',
        'wait_before_resend' => 'Please wait :seconds seconds before resending',
        'mail_subject' => 'Login Verification Code',
        'mail_greeting' => 'Hello :name',
        'mail_line' => 'Your login verification code is:',
        'mail_expiry' => 'This code will expire in :minutes minutes.',
        'mail_salutation' => 'Thank you for using our application',
    ],
];
