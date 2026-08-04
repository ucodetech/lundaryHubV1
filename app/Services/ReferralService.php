<?php

namespace App\Services;

use App\Models\BonusTransaction;
use App\Models\Order;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Link a newly registered user to their referrer using phone number or referral code.
     */
    public static function recordRegistration(User $newUser, ?string $referralInput): void
    {
        if (empty($referralInput)) {
            return;
        }

        $cleanInput = preg_replace('/[^0-9A-Za-z]/', '', $referralInput);

        $referrer = User::where('id', '!=', $newUser->id)
            ->where(function ($q) use ($referralInput, $cleanInput) {
                $q->where('referral_code', $referralInput)
                  ->orWhere('phone', $referralInput)
                  ->orWhere('phone', 'like', "%{$cleanInput}%");
            })->first();

        if ($referrer) {
            $newUser->update(['referred_by_id' => $referrer->id]);

            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $newUser->id,
                'referral_code' => $referrer->referral_code ?: $referrer->phone,
                'status' => 'pending',
                'reward_type' => 'customer_order',
                'referrer_reward' => 500.00,
                'referred_reward' => 200.00,
            ]);

            Log::info("Referral registered: User #{$newUser->id} referred by User #{$referrer->id} (Phone/Code: {$referralInput})");
        }
    }

    /**
     * Trigger bonus payout when a referred customer completes their first order.
     */
    public static function rewardFirstOrderCompletion(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer || !$customer->referred_by_id) {
            return;
        }

        // Verify if this is the customer's first completed order
        $completedCount = Order::where('customer_id', $customer->id)
            ->where('status', \App\Enums\OrderStatus::COMPLETED->value)
            ->count();

        if ($completedCount > 1) {
            return; // Already rewarded on previous order
        }

        $referral = Referral::where('referred_id', $customer->id)->where('status', 'pending')->first();
        if (!$referral) {
            return;
        }

        $referrer = User::find($customer->referred_by_id);
        if (!$referrer) {
            return;
        }

        $referrerReward = 500.00;
        $referredReward = 200.00;

        // Credit Referrer
        $referrer->increment('bonus_balance', $referrerReward);
        BonusTransaction::create([
            'user_id' => $referrer->id,
            'amount' => $referrerReward,
            'type' => 'earned_referral',
            'description' => "🎁 Referral Bonus: Recommending LaundryHub to {$customer->first_name} {$customer->last_name}",
        ]);

        // Credit Referred Customer
        $customer->increment('bonus_balance', $referredReward);
        BonusTransaction::create([
            'user_id' => $customer->id,
            'amount' => $referredReward,
            'type' => 'earned_referral',
            'description' => "🎉 Welcome Referral Bonus: First completed laundry order discount!",
        ]);

        $referral->update([
            'status' => 'rewarded',
            'reward_type' => 'customer_order',
            'referrer_reward' => $referrerReward,
            'referred_reward' => $referredReward,
            'rewarded_at' => now(),
        ]);

        Log::info("Referral rewarded for Order #{$order->order_number}: Referrer #{$referrer->id} (+₦500), Customer #{$customer->id} (+₦200)");
    }

    /**
     * Trigger bonus payout when a referred shop owner completes their first subscription.
     */
    public static function rewardShopOwnerSubscription(User $shopOwner): void
    {
        if (!$shopOwner->referred_by_id) {
            return;
        }

        $referral = Referral::where('referred_id', $shopOwner->id)->where('status', 'pending')->first();
        if (!$referral) {
            return;
        }

        $referrer = User::find($shopOwner->referred_by_id);
        if (!$referrer) {
            return;
        }

        $referrerReward = 1000.00;
        $referredReward = 500.00;

        $referrer->increment('bonus_balance', $referrerReward);
        BonusTransaction::create([
            'user_id' => $referrer->id,
            'amount' => $referrerReward,
            'type' => 'earned_referral',
            'description' => "🎁 Referral Bonus: Recommending Shop Owner {$shopOwner->first_name} to LaundryHub",
        ]);

        $shopOwner->increment('bonus_balance', $referredReward);
        BonusTransaction::create([
            'user_id' => $shopOwner->id,
            'amount' => $referredReward,
            'type' => 'earned_referral',
            'description' => "🎉 Welcome Referral Bonus: First LaundryHub Shop Owner Subscription!",
        ]);

        $referral->update([
            'status' => 'rewarded',
            'reward_type' => 'shop_subscription',
            'referrer_reward' => $referrerReward,
            'referred_reward' => $referredReward,
            'rewarded_at' => now(),
        ]);
    }
}
