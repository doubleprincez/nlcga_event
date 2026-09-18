<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'sid'           => env('TWILIO_ACCOUNT_SID', env('TWILIO_SID')),
        'token'         => env('TWILIO_AUTH_TOKEN'),
        'api_key'       => env('TWILIO_API_KEY'),
        'api_secret'    => env('TWILIO_API_SECRET'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', '+14155238886'),
        'templates'     => [
            'default'                         => env('TWILIO_DEFAULT_TEMPLATE_SID', env('TWILIO_TEMPLATE_PAYMENT_COMPLETED')),
            'payment_completed'              => env('TWILIO_TEMPLATE_PAYMENT_COMPLETED'),
            'payment_confirmed'              => env('TWILIO_TEMPLATE_PAYMENT_CONFIRMED', env('TWILIO_TEMPLATE_PAYMENT_COMPLETED')),
            'event_registration_confirmation' => env('TWILIO_TEMPLATE_EVENT_REGISTRATION'),
            'event_registration_marketing'    => env('TWILIO_TEMPLATE_EVENT_REGISTRATION_MKT'),
            'event_payment_confirmation'      => env('TWILIO_TEMPLATE_EVENT_PAYMENT'),
            'event_reminder'                  => env('TWILIO_TEMPLATE_EVENT_REMINDER'),
            'event_reminder_session'          => env('TWILIO_TEMPLATE_EVENT_REMINDER_SESSION'),
        ],
    ],

];
