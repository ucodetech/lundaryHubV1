<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopVirtualAccount;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaystackService;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class ShopVerificationController extends Controller
{
    public function __construct(
        protected ShopService $shopService,
        protected PaystackService $paystackService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'is_verified']);

        $query = Shop::with(['owner', 'settings', 'kycDocuments', 'virtualAccount'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($qOwner) use ($search) {
                      $qOwner->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
            $query->where('is_verified', (bool) $filters['is_verified']);
        }

        return Inertia::render('Admin/Shops/Index', [
            'shops' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function verify(Shop $shop): RedirectResponse
    {
        $this->shopService->verify($shop);

        $shop->update([
            'kyc_status'     => 'approved',
            'is_cac_verified' => ($shop->business_type === 'cac_registered'),
        ]);

        // 1. Provision Free Trial Subscription
        $trialPlan = SubscriptionPlan::where('key', 'shop_trial')->first();
        if ($trialPlan) {
            Subscription::updateOrCreate(
                ['shop_id' => $shop->id, 'role' => 'shop_owner', 'plan_key' => 'shop_trial'],
                [
                    'user_id'              => $shop->owner_id,
                    'subscription_plan_id' => $trialPlan->id,
                    'plan_name'            => $trialPlan->name,
                    'amount'               => 0.00,
                    'status'               => SubscriptionStatus::ACTIVE,
                    'starts_at'            => now(),
                    'ends_at'              => now()->addDays(30),
                    'payment_reference'    => 'FREE_TRIAL_' . Str::random(8),
                ]
            );
        }

        // 2. Provision Paystack Dedicated Virtual Account
        if (!$shop->virtualAccount) {
            $this->createVirtualAccountForShop($shop);
        }

        // Real-time Dashboard Reload Signal to Shop Owner
        try {
            event(new \App\Events\UserApprovedEvent(
                userId: $shop->owner_id,
                role: 'shop_owner',
                title: '🎉 Storefront Verified & Approved!',
                message: "Congratulations! '{$shop->name}' is now verified. A 1-Month Free Trial pass has been activated on your account."
            ));
        } catch (\Throwable $e) {
            Log::warning("Shop approval signal notice: " . $e->getMessage());
        }

        return back()->with('success', "Shop '{$shop->name}' verified with 1-Month Free Trial!");
    }

    public function generateVirtualAccount(Shop $shop): RedirectResponse
    {
        $result = $this->createVirtualAccountForShop($shop);

        if ($result['success']) {
            $accNum = $result['account_number'] ?? 'Assigned';
            $bank = $result['bank_name'] ?? 'Wema Bank';
            return back()->with('success', "🎉 Dedicated Virtual Account generated for '{$shop->name}'! Bank: {$bank}, Account #: {$accNum}");
        }

        return back()->with('error', "Paystack Virtual Account Notice: " . ($result['message'] ?? 'Could not provision account. Check logs.'));
    }

    protected function createVirtualAccountForShop(Shop $shop): array
    {
        $owner = $shop->owner;
        $phone = $owner->phone ?? '+2340000000000';

        $customerCode = $shop->virtualAccount?->paystack_customer_code;
        $customerId = $shop->virtualAccount?->paystack_customer_id;

        if (!$customerCode) {
            $customerResult = $this->paystackService->createPaystackCustomer(
                email:     $shop->email,
                firstName: $owner->first_name,
                lastName:  $owner->last_name,
                phone:     $phone,
            );

            if (!$customerResult['success']) {
                Log::warning("Shop #{$shop->id} Paystack customer creation failed: " . ($customerResult['message'] ?? 'Unknown error'));
                return $customerResult;
            }

            $customerCode = $customerResult['customer_code'];
            $customerId = $customerResult['customer_id'] ?? null;
        }

        $dvaResult = $this->paystackService->createDedicatedVirtualAccount(
            customerCode: $customerCode,
            preferredBank: 'wema-bank',
        );

        ShopVirtualAccount::updateOrCreate(
            ['shop_id' => $shop->id],
            [
                'owner_id'              => $shop->owner_id,
                'paystack_customer_id'  => $customerId,
                'paystack_customer_code'=> $customerCode,
                'account_number'        => $dvaResult['account_number'] ?? null,
                'account_name'          => $dvaResult['account_name'] ?? null,
                'bank_name'             => $dvaResult['bank_name'] ?? null,
                'bank_slug'             => $dvaResult['bank_slug'] ?? null,
                'bank_id'               => $dvaResult['bank_id'] ?? null,
                'paystack_account_id'   => $dvaResult['paystack_account_id'] ?? null,
                'preferred_bank'        => 'wema-bank',
                'is_active'             => true,
            ]
        );

        return $dvaResult;
    }

    public function suspend(Shop $shop): RedirectResponse
    {
        $this->shopService->suspend($shop);

        return back()->with('success', "Shop '{$shop->name}' has been suspended.");
    }
}
