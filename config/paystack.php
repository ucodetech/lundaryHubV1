<?php

$isProduction = env('APP_ENV') === 'production';

return [
    'publicKey' => $isProduction
        ? env('PAYSTACK_LPUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY', 'pk_test_laundryhub_dummy'))
        : env('PAYSTACK_PUBLIC_KEY', 'pk_test_laundryhub_dummy'),
    'secretKey' => $isProduction
        ? env('PAYSTACK_LSECRET_KEY', env('PAYSTACK_SECRET_KEY', 'sk_test_laundryhub_dummy'))
        : env('PAYSTACK_SECRET_KEY', 'sk_test_laundryhub_dummy'),
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    'merchantEmail' => env('PAYSTACK_MERCHANT_EMAIL', 'billing@laundryhub.ng'),
];
