<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | outcome such as failure due to an invalid password / reset token.
    |
    */

    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed your password reset link.',
    'throttled' => 'Please wait before retrying.',
    'token' => 'This password reset token is invalid.',
    'user' => "We can't find a user with that email address.",

    // Email notification (ResetPasswordNotification)
    'notification_subject' => 'Reset Password Notification',
    'notification_reason' => 'You are receiving this email because we received a password reset request for your account.',
    'notification_action' => 'Reset Password',
    'notification_expire' => 'This password reset link will expire in :count minutes.',
    'notification_no_action' => 'If you did not request a password reset, no further action is required.',

];
