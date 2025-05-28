<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'reverb/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173', // Vite dev server
        'http://localhost:8080', // Alternative port
        env('FRONTEND_URL', 'http://localhost:3000')
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];