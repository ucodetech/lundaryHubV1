<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Services\RiderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiderProfileController extends Controller
{
    public function __construct(protected RiderService $riderService)
    {
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
            'file' => 'required|image|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();
        $profile = $user->riderProfile ?? $this->riderService->createProfile($user, []);

        $path = $request->file('file')->store('kyc_documents', 'public');

        $docType = DocumentType::from($request->document_type);
        $this->riderService->uploadKycDocument($profile, $docType, $path);

        return back()->with('success', 'KYC Document uploaded for verification.');
    }

    public function toggleOnline(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        if (!$profile) {
            return back()->with('error', 'Rider profile not found.');
        }

        $isOnline = $this->riderService->toggleOnline($profile);

        return back()->with('success', $isOnline ? 'You are now ONLINE' : 'You are now OFFLINE');
    }
}
