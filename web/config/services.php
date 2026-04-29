<?php

return [

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

    'api' => [
        // Si API_URL est défini (cas Docker via env vars), on l'utilise.
        // Sinon, fichier /.dockerenv présent => Docker, fallback http://api:8888 ; sinon local => http://localhost:8888.
        'url' => env('API_URL') ?: (file_exists('/.dockerenv') ? 'http://api:8888' : 'http://localhost:8888'),
    ],

];
