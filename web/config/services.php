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
        // URL serveur-à-serveur (PHP → Go API via réseau Docker ou local).
        'url' => env('API_URL') ?: (file_exists('/.dockerenv') ? 'http://api:8888' : 'http://localhost:8888'),
        // URL publique utilisée par le navigateur (fetch JS) — toujours l'hôte accessible depuis la machine cliente.
        // En prod, l'API est servie sur le même domaine HTTPS (Caddy route /api/*), donc on retombe sur APP_URL.
        // En dev (APP_URL en http://localhost:8000), on garde l'accès direct à l'API sur :8888.
        'public_url' => env('API_PUBLIC_URL')
            ?: (str_starts_with((string) env('APP_URL', ''), 'https') ? rtrim(env('APP_URL'), '/') : 'http://localhost:8888'),
    ],

    // Secret partagé avec l'API Go pour vérifier la signature des JWT.
    // Passé par config (et non env() direct) pour rester lisible après config:cache.
    'jwt' => [
        'secret' => env('JWT_SECRET'),
    ],

];
