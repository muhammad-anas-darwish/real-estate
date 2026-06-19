<?php

return [
    'secret_key' => env('STRIPE_SECRET', ''),
    'public_key' => env('STRIPE_PUBLIC', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'checkout_success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL', '/checkout/success'),
    'checkout_cancel_url' => env('STRIPE_CHECKOUT_CANCEL_URL', '/checkout/cancel'),
];
