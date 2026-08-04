<?php

namespace App\Http\Controllers;

use App\Models\BonusTransaction;
use App\Models\Referral;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure user has a referral code (phone number)
        if (empty($user->referral_code)) {
            $user->update(['referral_code' => $user->phone]);
        }

        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referred')
            ->latest()
            ->paginate(15);

        $transactions = BonusTransaction::where('user_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        $totalEarned = BonusTransaction::where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->sum('amount');

        return Inertia::render('Referrals/Index', [
            'referralCode' => $user->referral_code,
            'referralLink' => url("/register?ref={$user->referral_code}"),
            'bonusBalance' => (float) $user->bonus_balance,
            'totalEarned' => (float) $totalEarned,
            'referrals' => $referrals,
            'transactions' => $transactions,
        ]);
    }
}
