<?php

namespace App\Http\Controllers;

use App\Models\DisputeMessage;
use App\Models\Order;
use App\Models\OrderDispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $disputes = OrderDispute::where('reporter_id', $user->id)
            ->with(['order.shop', 'resolvedBy'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Disputes/Index', [
            'disputes' => $disputes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'against_type' => 'required|string|in:shop,rider,platform',
            'reason' => 'required|string|in:damaged_garment,missing_item,late_delivery,overcharge,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'photos.*' => 'nullable|image|max:10240', // Max 10MB per photo
        ]);

        $user = $request->user();
        $order = Order::findOrFail($validated['order_id']);

        // Store photo evidence
        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('dispute_evidence', 'public');
            }
        }

        $disputeNumber = 'DISP-' . strtoupper(Str::random(6));

        $dispute = OrderDispute::create([
            'dispute_number' => $disputeNumber,
            'order_id' => $order->id,
            'reporter_id' => $user->id,
            'against_type' => $validated['against_type'],
            'reason' => $validated['reason'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'evidence_photos' => $photoPaths,
            'status' => 'open',
        ]);

        $order->logStatusChange($order->status->value ?? (string)$order->status, $user, "⚠️ Dispute ticket #{$disputeNumber} opened: {$validated['subject']}");

        return back()->with('success', "⚠️ Dispute ticket #{$disputeNumber} submitted successfully! Our support team will review it within 24 hours.");
    }

    public function show(Request $request, OrderDispute $dispute): Response
    {
        $user = $request->user();

        // Check permission (Reporter, Super Admin, Support)
        if ($dispute->reporter_id !== $user->id && !in_array($user->role->value ?? $user->role, ['super_admin', 'support'])) {
            abort(403, 'Unauthorized access to dispute ticket.');
        }

        $dispute->load([
            'order.shop',
            'order.rider',
            'order.statusLogs.user',
            'reporter',
            'resolvedBy',
            'messages.user',
        ]);

        return Inertia::render('Disputes/Show', [
            'dispute' => $dispute,
        ]);
    }

    public function reply(Request $request, OrderDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $user = $request->user();

        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = $file->store('dispute_attachments', 'public');
            }
        }

        DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
            'attachments' => $attachmentPaths,
        ]);

        if ($dispute->status === 'open' && in_array($user->role->value ?? $user->role, ['super_admin', 'support'])) {
            $dispute->update(['status' => 'under_review']);
        }

        return back()->with('success', 'Message sent.');
    }
}
