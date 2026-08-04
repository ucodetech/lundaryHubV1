<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\UserBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RiderPayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Calculate total delivery fee earnings from completed orders
        $totalEarned = Order::where('rider_id', $user->id)
            ->where('status', \App\Enums\OrderStatus::COMPLETED->value)
            ->sum('delivery_fee');

        // Total amount in pending/approved/paid withdrawal requests
        $totalWithdrawnOrPending = PayoutRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        $availableBalance = max(0, (float)$totalEarned - (float)$totalWithdrawnOrPending);

        $bankAccount = UserBankAccount::where('user_id', $user->id)->first();

        $payouts = PayoutRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return Inertia::render('Rider/Payouts/Index', [
            'totalEarned' => (float) $totalEarned,
            'availableBalance' => (float) $availableBalance,
            'bankAccount' => $bankAccount,
            'payouts' => $payouts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $bankAccount = UserBankAccount::where('user_id', $user->id)->first();
        if (!$bankAccount) {
            return back()->with('error', 'Please verify and save your bank account details before requesting a withdrawal.');
        }

        // Recalculate available balance
        $totalEarned = Order::where('rider_id', $user->id)
            ->where('status', \App\Enums\OrderStatus::COMPLETED->value)
            ->sum('delivery_fee');

        $totalWithdrawnOrPending = PayoutRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        $availableBalance = max(0, (float)$totalEarned - (float)$totalWithdrawnOrPending);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000|max:' . $availableBalance,
        ], [
            'amount.max' => 'Requested payout amount exceeds your available balance of ₦' . number_format($availableBalance, 2),
            'amount.min' => 'Minimum withdrawal payout amount is ₦1,000.00',
        ]);

        $payoutNumber = 'PAY-' . strtoupper(Str::random(6));

        PayoutRequest::create([
            'payout_number' => $payoutNumber,
            'user_id' => $user->id,
            'role' => $user->role->value ?? (string)$user->role,
            'amount' => $validated['amount'],
            'bank_name' => $bankAccount->bank_name,
            'account_number' => $bankAccount->account_number,
            'account_name' => $bankAccount->account_name,
            'status' => 'pending',
        ]);

        return back()->with('success', "💳 Withdrawal request #{$payoutNumber} for ₦" . number_format($validated['amount'], 2) . " submitted successfully! Super Admin will process payment to {$bankAccount->bank_name}.");
    }
}
