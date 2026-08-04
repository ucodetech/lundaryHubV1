<?php

return [
    'plans' => [
        'shop_trial' => [
            'key' => 'shop_trial',
            'name' => '1-Month Free Trial',
            'target_role' => 'shop_owner',
            'price' => 0.00,
            'interval_days' => 30,
            'order_limit' => 15,
            'description' => 'Free 30-day welcome trial for newly verified dry cleaners to test LaundryHub features.',
            'features' => [
                '30 Days Free Access',
                'Max 15 Customer Orders limit',
                'Storefront & Item Catalog Setup',
                'Walk-In Legacy Order Logging',
            ],
        ],

        'rider_pass' => [
            'key' => 'rider_pass',
            'name' => 'Rider Monthly Dispatch Pass',
            'target_role' => 'rider',
            'price' => 2000.00,
            'interval_days' => 30,
            'order_limit' => null,
            'description' => 'Flat monthly access pass to receive nearby customer pickup and delivery dispatches.',
            'features' => [
                'Unlimited Delivery Dispatches',
                'Live WebRTC Selfie Verification',
                'Keep 100% of Delivery Earnings',
            ],
        ],
        'shop_starter' => [
            'key' => 'shop_starter',
            'name' => 'Starter Dry Cleaner Plan',
            'target_role' => 'shop_owner',
            'price' => 10000.00,
            'interval_days' => 30,
            'order_limit' => 50,
            'description' => 'Essential storefront management for growing independent dry cleaners.',
            'features' => [
                'Up to 50 Customer Orders / month',
                'Storefront Settings & Location Pin',
                'Master Template Category Import',
                'Doorstep Delivery & Store Pickup Toggles',
            ],
        ],
        'shop_pro' => [
            'key' => 'shop_pro',
            'name' => 'Pro Growth Plan',
            'target_role' => 'shop_owner',
            'price' => 25000.00,
            'interval_days' => 30,
            'order_limit' => null,
            'description' => 'Unlimited order volume, priority marketplace listing, and legacy customer linking.',
            'features' => [
                'Unlimited Customer Orders / month',
                'Priority Storefront Marketplace Placement',
                'Walk-In / Legacy Order Logging & Linking',
                'Custom Pricing Matrix Engine',
            ],
        ],
        'shop_enterprise' => [
            'key' => 'shop_enterprise',
            'name' => 'Enterprise Master Plan',
            'target_role' => 'shop_owner',
            'price' => 50000.00,
            'interval_days' => 30,
            'order_limit' => null,
            'description' => 'Multi-branch operations, dedicated account support, and advanced analytics.',
            'features' => [
                'All Pro Growth Features Included',
                'Multi-Branch & Staff Management',
                'Dedicated 24/7 Account Support',
                'Custom Domain & Brand Customization',
            ],
        ],
    ],
];
