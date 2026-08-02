<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiderProfile;
use App\Services\RiderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiderVerificationController extends Controller
{
    public function __construct(protected RiderService $riderService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'kyc_status', 'vehicle_type', 'is_online']);

        $query = RiderProfile::with(['user', 'kycDocuments'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qUser) use ($search) {
                      $qUser->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['kyc_status'])) {
            $query->where('kyc_status', $filters['kyc_status']);
        }

        if (!empty($filters['vehicle_type'])) {
            $query->where('vehicle_type', $filters['vehicle_type']);
        }

        if (isset($filters['is_online']) && $filters['is_online'] !== '') {
            $query->where('is_online', (bool) $filters['is_online']);
        }

        return Inertia::render('Admin/Riders/Index', [
            'riders' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function approve(RiderProfile $rider): RedirectResponse
    {
        $this->riderService->approveKyc($rider);

        return back()->with('success', "Rider '{$rider->user->name}' KYC approved.");
    }

    public function reject(Request $request, RiderProfile $rider): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $this->riderService->rejectKyc($rider, $request->reason);

        return back()->with('success', "Rider '{$rider->user->name}' KYC rejected.");
    }
}
