<?php

return [
    'publicKey' => env('PAYSTACK_PUBLIC_KEY', 'pk_test_laundryhub_dummy'),
    'secretKey' => env('PAYSTACK_SECRET_KEY', 'sk_test_laundryhub_dummy'),
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    'merchantEmail' => env('PAYSTACK_MERCHANT_EMAIL', 'billing@laundryhub.ng'),
];
