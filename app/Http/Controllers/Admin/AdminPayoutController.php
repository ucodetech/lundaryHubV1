<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPayoutController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'role', 'search']);

        $query = PayoutRequest::with(['user', 'processedBy'])->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payout_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $stats = [
            'pending_count' => PayoutRequest::where('status', 'pending')->count(),
            'pending_amount' => (float) PayoutRequest::where('status', 'pending')->sum('amount'),
            'total_paid' => (float) PayoutRequest::where('status', 'paid')->sum('amount'),
        ];

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $query->paginate(20)->withQueryString(),
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function approve(Request $request, PayoutRequest $payout): RedirectResponse
    {
        $admin = $request->user();

        $payout->update([
            'status' => 'paid',
            'processed_by_id' => $admin->id,
            'processed_at' => now(),
        ]);

        return back()->with('success', "✅ Payout request #{$payout->payout_number} for ₦" . number_format($payout->amount, 2) . " marked as PAID to {$payout->account_name} ({$payout->bank_name}).");
    }

    public function reject(Request $request, PayoutRequest $payout): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $admin = $request->user();

        $payout->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'processed_by_id' => $admin->id,
            'processed_at' => now(),
        ]);

        return back()->with('success', "Payout request #{$payout->payout_number} rejected. Balance returned to rider available wallet.");
    }
}
