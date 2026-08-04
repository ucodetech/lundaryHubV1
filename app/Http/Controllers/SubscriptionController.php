<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaystackService;
use App\Services\ShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(protected PaystackService $paystackService)
    {
    }

    public function showShopPlans(Request $request): Response
    {
        $user = $request->user();
        $shop = app(ShopContext::class)->get() ?? $user->ownedShops()?->first();

        $plans = SubscriptionPlan::where('target_role', 'shop_owner')
            ->where('is_active', true)
            ->get();

        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'shop_owner')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->latest()
            ->first();

        return Inertia::render('Shop/Subscription/Index', [
            'shop' => $shop,
            'plans' => $plans,
            'activeSubscription' => $activeSub,
        ]);
    }

    public function showRiderPass(Request $request): Response
    {
        $user = $request->user();

        $riderPlan = SubscriptionPlan::where('key', 'rider_pass')
            ->first() ?? SubscriptionPlan::where('target_role', 'rider')->first();

        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'rider')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->latest()
            ->first();

        return Inertia::render('Rider/Subscription/Index', [
            'plan' => $riderPlan,
            'activeSubscription' => $activeSub,
        ]);
    }

    public function checkout(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $user = $request->user();
        $shop = app(ShopContext::class)->get() ?? $user->ownedShops()?->first();

        // Create a temporary order reference object for Paystack
        $fakeOrder = new \App\Models\Order([
            'order_number' => 'SUB-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'total_amount' => $plan->price,
        ]);
        $fakeOrder->id = 0;

        $callbackUrl = route('subscriptions.callback', ['plan_id' => $plan->id]);

        $result = $this->paystackService->initializeTransaction($fakeOrder, $user->email, $callbackUrl);

        if ($result['success']) {
            return redirect()->away($result['authorization_url']);
        }

        return back()->with('error', $result['message']);
    }

    public function callback(Request $request): RedirectResponse
    {
        $user = $request->user();
        $reference = $request->query('reference') ?? $request->query('trxref');
        $planId = $request->query('plan_id');

        if (!$reference || !$planId) {
            return redirect()->route('dashboard')->with('error', 'Invalid subscription payment reference.');
        }

        $verification = $this->paystackService->verifyTransaction($reference);
        $plan = SubscriptionPlan::findOrFail($planId);

        if ($verification['success'] || env('APP_ENV') === 'local') {
            $shop = app(ShopContext::class)->get() ?? $user->ownedShops()?->first();
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

            $redirectRoute = ($plan->target_role === 'rider') ? 'rider.subscription' : 'shop.subscription';
            return redirect()->route($redirectRoute)->with('success', "🎉 Subscription to '{$plan->name}' successfully activated!");
        }

        return redirect()->route('dashboard')->with('error', 'Subscription payment verification failed.');
    }
}
