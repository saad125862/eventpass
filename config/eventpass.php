<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployed Version
    |--------------------------------------------------------------------------
    |
    | Shown by the /health endpoint so every deploy can be traced to a commit.
    | APP_VERSION is set by CI/Docker builds; Render provides RENDER_GIT_COMMIT.
    |
    */

    'version' => env('APP_VERSION') ?: env('RENDER_GIT_COMMIT') ?: 'dev',

    /*
    |--------------------------------------------------------------------------
    | Booking
    |--------------------------------------------------------------------------
    */

    // Minutes a pending order keeps its tickets reserved before they are released.
    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 10),

    // Maximum tickets a single order may contain across all ticket types.
    'max_tickets_per_order' => (int) env('BOOKING_MAX_TICKETS', 10),

    /*
    |--------------------------------------------------------------------------
    | Payments (test mode only)
    |--------------------------------------------------------------------------
    */

    // Shared secret used to sign payment webhooks (HMAC-SHA256).
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Demo data
    |--------------------------------------------------------------------------
    */

    'demo_password' => env('DEMO_PASSWORD', 'eventpass-demo'),

    // Admin demo login; when unset a random password is generated (nobody can sign in).
    'admin_password' => env('ADMIN_PASSWORD'),

];
