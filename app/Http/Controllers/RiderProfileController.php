<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\CloudinaryService;
use App\Services\RiderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiderProfileController extends Controller
{
    public function __construct(
        protected RiderService $riderService,
        protected CloudinaryService $cloudinaryService
    ) {
    }

    public function show(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->riderProfile ?? $this->riderService->createProfile($user, []);

        return Inertia::render('Rider/Profile', [
            'profile' => $profile->load('kycDocuments'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile ?? $this->riderService->createProfile($user, []);

        $validated = $request->validate([
            'vehicle_type' => 'required|string|in:bicycle,motorcycle,tricycle,van,car',
            'vehicle_plate' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:50',
        ]);

        $profile->update($validated);

        return back()->with('success', 'Rider details updated.');
    }

    public function uploadKyc(Request $request): RedirectResponse
    {
        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:10240',
        ]);

        $user = $request->user();
        $profile = $user->riderProfile ?? $this->riderService->createProfile($user, []);

        $cloudinaryUrl = $this->cloudinaryService->upload($request->file('file'), "laundryhub/riders/{$profile->id}/kyc");

        if (! $cloudinaryUrl) {
            return back()->with('error', 'Failed to upload document to Cloudinary storage. Please try again.');
        }

        $docType = DocumentType::from($request->document_type);
        $this->riderService->uploadKycDocument($profile, $docType, $cloudinaryUrl);

        // Real-time Push Notification to Admin
        try {
            event(new \App\Events\AdminNotificationEvent(
                title: '🛵 Rider KYC Document Uploaded!',
                message: "Rider {$user->first_name} {$user->last_name} uploaded {$docType->label()} for audit.",
                type: 'kyc_submitted',
                url: '/admin/riders'
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Rider KYC notification notice: " . $e->getMessage());
        }

        return back()->with('success', 'Rider KYC document uploaded to Cloudinary successfully.');
    }

    public function toggleOnline(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        if (!$profile) {
            return back()->with('error', 'Rider profile not found.');
        }

        // Only allow going ONLINE if the rider has an active paid subscription
        if (!$profile->is_online) {
            $hasActivePass = Subscription::where('user_id', $user->id)
                ->where('role', 'rider')
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where('ends_at', '>', now())
                ->exists();

            if (!$hasActivePass) {
                return redirect()->route('rider.subscription')
                    ->with('warning', '🚫 You need an active Monthly Rider Pass to go online and accept deliveries. Please pay your ₦2,000 monthly pass to get started.');
            }
        }

        $isOnline = $this->riderService->toggleOnline($profile);

        return back()->with('success', $isOnline ? '🟢 You are now ONLINE and ready for dispatches!' : '🔴 You are now OFFLINE');
    }
}
