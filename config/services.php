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
    
    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
    ],
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_KEY'),
    ],
    'sepay'=>[

            'api_key' => env('SEPAY_API_KEY'),
            'api_url' => env('SEPAY_API_URL'),
            'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
            'merchant_id'=> env('SEPAY_MERCHANT_ID'),
            'merchant_secret'=> env('SEPAY_MERCHANT_SECRET_KEY'),
            'environment' => env('SEPAY_ENVIRONMENT', 'sandbox'),
    ],
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],
    'ollama'=>[
        'api_url' => env('OLLAMA_API_URL', 'http://localhost:11434/api/generate'),
        'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
    ],
    'clamav' => [
    'url' => env('CLAMAV_SCAN_URL'),
    ],
];
