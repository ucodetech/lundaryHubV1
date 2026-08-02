<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopVerificationController extends Controller
{
    public function __construct(protected ShopService $shopService)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'is_verified']);

        $query = Shop::with(['owner', 'settings', 'kycDocuments'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($qOwner) use ($search) {
                      $qOwner->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
            $query->where('is_verified', (bool) $filters['is_verified']);
        }

        return Inertia::render('Admin/Shops/Index', [
            'shops' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function verify(Shop $shop): RedirectResponse
    {
        $this->shopService->verify($shop);

        $shop->update([
            'kyc_status' => 'approved',
            'is_cac_verified' => ($shop->business_type === 'cac_registered'),
        ]);

        return back()->with('success', "Shop '{$shop->name}' verified and activated successfully.");
    }

    public function suspend(Shop $shop): RedirectResponse
    {
        $this->shopService->suspend($shop);

        return back()->with('success', "Shop '{$shop->name}' has been suspended.");
    }
}
