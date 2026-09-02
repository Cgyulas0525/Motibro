<?php

return [
    'email' => env('MOTIBRO_EMAIL'),
    'password' => env('MOTIBRO_PASSWORD'),
    'base_url' => env('MOTIBRO_BASE_URL'),
    'portal_site_id' => env('MOTIBRO_PORTAL_SITE_ID', '553'),
    'waitlist' => filter_var(env('MOTIBRO_WAITLIST', false), FILTER_VALIDATE_BOOL),
    'headless' => filter_var(env('MOTIBRO_HEADLESS', true), FILTER_VALIDATE_BOOL),
    'weeks_ahead' => (int) env('MOTIBRO_WEEKS_AHEAD', 3),
    'notify_email' => env('MOTIBRO_NOTIFY_EMAIL'),
    'node_binary' => env('NODE_BINARY', 'node'),
    'script_path' => base_path('scripts/motibro-book.mjs'),
    'process_timeout' => (int) env('MOTIBRO_PROCESS_TIMEOUT', 600),
];
