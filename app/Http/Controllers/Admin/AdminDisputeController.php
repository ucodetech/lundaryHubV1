<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusTransaction;
use App\Models\OrderDispute;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDisputeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'reason', 'search']);

        $query = OrderDispute::with(['order.shop', 'reporter', 'resolvedBy'])->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('dispute_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('reporter', function ($uq) use ($search) {
                      $uq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function resolve(Request $request, OrderDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:resolved_refund,resolved_compensated,resolved_rejected,closed',
            'resolution_notes' => 'required|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $admin = $request->user();
        $refundAmount = (float) ($validated['refund_amount'] ?? 0);

        // Process bonus wallet refund if resolved_refund or resolved_compensated
        if (in_array($validated['status'], ['resolved_refund', 'resolved_compensated']) && $refundAmount > 0) {
            $reporter = User::findOrFail($dispute->reporter_id);
            $reporter->increment('bonus_balance', $refundAmount);

            BonusTransaction::create([
                'user_id' => $reporter->id,
                'amount' => $refundAmount,
                'type' => 'dispute_refund',
                'description' => "🛡️ Dispute Settlement Credit (#{$dispute->dispute_number}): " . $validated['resolution_notes'],
            ]);
        }

        $dispute->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_by_id' => $admin->id,
            'resolved_at' => now(),
        ]);

        $statusLabel = str_replace('_', ' ', $validated['status']);
        $dispute->order->logStatusChange(
            $dispute->order->status->value ?? (string)$dispute->order->status,
            $admin,
            "⚖️ Dispute ticket #{$dispute->dispute_number} marked as {$statusLabel}. Notes: {$validated['resolution_notes']}"
        );

        return back()->with('success', "Dispute #{$dispute->dispute_number} has been resolved as '{$statusLabel}'.");
    }
}
