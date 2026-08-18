<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Order;
use App\Models\ShopVirtualAccount;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paystack.secretKey', env('PAYSTACK_SECRET_KEY', 'sk_test_laundryhub_dummy'));
        $this->baseUrl = config('paystack.paymentUrl', 'https://api.paystack.co');
    }

    public function initializeTransaction(Order $order, string $email, string $callbackUrl): array
    {
        $amountInKobo = (int) round(((float) $order->total_amount) * 100);

        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'amount' => $amountInKobo,
                    'email' => $email,
                    'reference' => $order->order_number,
                    'callback_url' => $callbackUrl,
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'shop_id' => $order->shop_id,
                        'shop_name' => $order->shop?->name,
                        'shop_virtual_account' => $order->shop?->virtualAccount?->account_number ?? null,
                        'shop_bank' => $order->shop?->virtualAccount?->bank_name ?? null,
                        'custom_fields' => [
                            [
                                'display_name' => 'Order Number',
                                'variable_name' => 'order_number',
                                'value' => $order->order_number,
                            ],
                            [
                                'display_name' => 'Shop Virtual Account',
                                'variable_name' => 'shop_virtual_account',
                                'value' => $order->shop?->virtualAccount?->account_number ? "{$order->shop->virtualAccount->account_number} ({$order->shop->virtualAccount->bank_name})" : 'N/A',
                            ],
                            [
                                'display_name' => 'Fulfillment Type',
                                'variable_name' => 'fulfillment_type',
                                'value' => $order->fulfillment_type->value ?? $order->fulfillment_type,
                            ],
                        ],
                    ],
                ]);

            if ($response->successful() && $response->json('status')) {
                return [
                    'success' => true,
                    'authorization_url' => $response->json('data.authorization_url'),
                    'access_code' => $response->json('data.access_code'),
                    'reference' => $response->json('data.reference'),
                ];
            }

            Log::error('Paystack initialization response:', ['response' => $response->json()]);

            if (env('APP_ENV') === 'local') {
                Log::info("Paystack local fallback redirecting to callback for order #{$order->order_number}");
                return [
                    'success' => true,
                    'authorization_url' => $callbackUrl . '?reference=' . $order->order_number,
                    'access_code' => 'LOCAL_TEST_CODE',
                    'reference' => $order->order_number,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Failed to initialize Paystack payment.',
            ];
        } catch (\Exception $e) {
            Log::error('Paystack API Exception', ['error' => $e->getMessage()]);

            if (env('APP_ENV') === 'local') {
                return [
                    'success' => true,
                    'authorization_url' => $callbackUrl . '?reference=' . $order->order_number,
                    'access_code' => 'LOCAL_TEST_CODE',
                    'reference' => $order->order_number,
                ];
            }

            return [
                'success' => false,
                'message' => 'Paystack connection error: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if ($response->successful() && $response->json('status')) {
                $data = $response->json('data');
                if ($data['status'] === 'success') {
                    $this->handleChargeSuccess($data);
                    return ['success' => true, 'data' => $data];
                }
            }

            return ['success' => false, 'message' => 'Payment verification failed or status unpaid.'];
        } catch (\Exception $e) {
            Log::error('Paystack verification error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        $amount = isset($data['amount']) ? ((float) $data['amount']) / 100 : 0;
        $customerEmail = $data['customer']['email'] ?? null;
        $metadata = $data['metadata'] ?? [];

        Log::info("Paystack handleChargeSuccess triggered for ref: {$reference}, amount: ₦{$amount}");

        // 1. Check if reference is a Subscription Payment (starts with SUB-)
        if (Str::startsWith($reference, 'SUB-') || isset($metadata['plan_id'])) {
            $this->processSubscriptionPayment($reference, $data);
            return;
        }

        // 2. Check if reference matches a Customer Order (starts with LHD-)
        $order = Order::where('order_number', $reference)->first();
        if ($order) {
            $order->update([
                'payment_status' => PaymentStatus::PAID,
                'status' => ($order->status === OrderStatus::PENDING) ? OrderStatus::CONFIRMED : $order->status,
                'payment_method' => 'paystack',
            ]);
            Log::info("Order #{$reference} marked as PAID via Paystack webhook.");
            return;
        }

        // 3. Dedicated Virtual Account Payment Notification
        $customerCode = $data['customer']['customer_code'] ?? null;
        if ($customerCode) {
            $virtualAccount = ShopVirtualAccount::where('paystack_customer_code', $customerCode)->first();
            if ($virtualAccount) {
                Log::info("Virtual Account Payment received for Shop #{$virtualAccount->shop_id}: ₦{$amount}");
            }
        }
    }

    protected function processSubscriptionPayment(string $reference, array $data): void
    {
        $metadata = $data['metadata'] ?? [];
        $planId = $metadata['plan_id'] ?? null;
        $userEmail = $data['customer']['email'] ?? null;

        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            Log::error("Subscription webhook failed: User not found for email {$userEmail}");
            return;
        }

        $plan = null;
        if ($planId) {
            $plan = SubscriptionPlan::find($planId);
        }

        if (!$plan && isset($metadata['plan_key'])) {
            $plan = SubscriptionPlan::where('key', $metadata['plan_key'])->first();
        }

        if (!$plan) {
            Log::warning("Subscription webhook notice: Plan could not be determined for ref {$reference}");
            return;
        }

        $shop = $user->ownedShops()?->first();
        $intervalDays = $plan->interval_days ?? 30;

        Subscription::create([
            'user_id' => $user->id,
            'shop_id' => $shop?->id,
            'subscription_plan_id' => $plan->id,
            'role' => $plan->target_role,
            'plan_key' => $plan->key,
            'plan_name' => $plan->name,
            'amount' => $plan->price,
            'status' => SubscriptionStatus::ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($intervalDays),
            'payment_reference' => $reference,
        ]);

        Log::info("Subscription '{$plan->name}' activated via webhook for user #{$user->id}");
    }

    public function handleDvaAssigned(array $data): void
    {
        $customerCode = $data['customer']['customer_code'] ?? null;
        $accountNumber = $data['account_number'] ?? null;
        $accountName = $data['account_name'] ?? null;
        $bankName = $data['bank']['name'] ?? 'Wema Bank';

        if ($customerCode && $accountNumber) {
            $virtualAcc = ShopVirtualAccount::where('paystack_customer_code', $customerCode)->first();
            if ($virtualAcc) {
                $virtualAcc->update([
                    'account_number' => $accountNumber,
                    'account_name' => $accountName,
                    'bank_name' => $bankName,
                    'is_active' => true,
                ]);
                Log::info("DVA assigned webhook updated for Shop #{$virtualAcc->shop_id}: {$accountNumber} ({$bankName})");
            }
        }
    }

    public function markOrderAsPaid(string $orderNumber, array $paymentDetails = []): ?Order
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if ($order) {
            $order->update([
                'payment_status' => PaymentStatus::PAID,
                'status' => ($order->status === OrderStatus::PENDING) ? OrderStatus::CONFIRMED : $order->status,
                'payment_method' => 'paystack',
            ]);
            Log::info("Order #{$orderNumber} marked as PAID via Paystack.");
        }

        return $order;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->secretKey)) return false;
        $calculatedSignature = hash_hmac('sha512', $payload, $this->secretKey);
        return hash_equals($calculatedSignature, $signature);
    }

    public function createPaystackCustomer(string $email, string $firstName, string $lastName, string $phone): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/customer", [
                    'email'      => $email,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'phone'      => $phone,
                ]);

            $json = $response->json();

            if ($response->successful() && $json['status']) {
                return [
                    'success'       => true,
                    'customer_code' => $json['data']['customer_code'],
                    'customer_id'   => $json['data']['id'],
                ];
            }

            Log::error('Paystack createCustomer failed', ['response' => $json]);
            return ['success' => false, 'message' => $json['message'] ?? 'Failed to create Paystack customer.'];
        } catch (\Exception $e) {
            Log::error('Paystack createCustomer exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createDedicatedVirtualAccount(string $customerCode, string $preferredBank = 'wema-bank'): array
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/dedicated_account", [
                    'customer'       => $customerCode,
                    'preferred_bank' => $preferredBank,
                ]);

            $json = $response->json();

            if ($response->successful() && $json['status']) {
                $data = $json['data'];
                return [
                    'success'             => true,
                    'paystack_account_id' => $data['id'] ?? null,
                    'account_number'      => $data['account_number'] ?? null,
                    'account_name'        => $data['account_name'] ?? null,
                    'bank_name'           => $data['bank']['name'] ?? null,
                    'bank_slug'           => $data['bank']['slug'] ?? null,
                    'bank_id'             => $data['bank']['id'] ?? null,
                ];
            }

            Log::error('Paystack createDVA failed', ['response' => $json]);
            return ['success' => false, 'message' => $json['message'] ?? 'Failed to create dedicated virtual account.'];
        } catch (\Exception $e) {
            Log::error('Paystack createDVA exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
