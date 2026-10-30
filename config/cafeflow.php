<?php

return [
    'currency' => env('CAFEFLOW_CURRENCY', 'USD'),
    'currency_symbol' => '$',
    'tax_rate' => (float) env('CAFEFLOW_TAX_RATE', 0.08),
    'delivery_fee' => (string) env('CAFEFLOW_DELIVERY_FEE', '4.50'),
    'free_delivery_over' => (string) env('CAFEFLOW_FREE_DELIVERY_OVER', '50.00'),
    'min_delivery_order' => '10.00',
    'max_item_quantity' => 20,
    'max_cart_lines' => 30,

    // When false, confirming an order fails if any ingredient would drop below zero.
    // Individual inventory items can still opt in via inventory_items.allow_negative.
    'allow_negative_stock' => (bool) env('CAFEFLOW_ALLOW_NEGATIVE_STOCK', false),
    'low_stock_email' => env('CAFEFLOW_LOW_STOCK_EMAIL'),
    // Minimum gap between repeat low-stock alerts for the same ingredient.
    'low_stock_renotify_hours' => 12,

    'demo_password' => env('CAFEFLOW_DEMO_PASSWORD', 'CafeFlow!2026'),

    'uploads' => [
        'max_kb' => 4096,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_dimension' => 2400, // larger images are downscaled on upload
    ],

    'analytics' => [
        'url' => rtrim((string) env('ANALYTICS_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
        'key' => env('ANALYTICS_SERVICE_KEY'),
        'timeout' => (int) env('ANALYTICS_TIMEOUT', 5),
        'retries' => (int) env('ANALYTICS_RETRIES', 2),
        'cache_seconds' => (int) env('ANALYTICS_CACHE_SECONDS', 300),
    ],

    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'mock'),
        'mock' => [
            'webhook_secret' => env('MOCK_PAYMENT_WEBHOOK_SECRET'),
        ],
        'stripe' => [
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'api_base' => 'https://api.stripe.com/v1',
            'tolerance_seconds' => 300,
        ],
    ],
];
