<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(protected ShopService $shopService)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Shop/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'business_type' => 'nullable|string|in:cac_registered,sole_proprietorship',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pickup_radius_km' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'offers_home_delivery' => 'nullable|boolean',
            'offers_store_pickup' => 'nullable|boolean',
        ]);

        $shop = $this->shopService->create($request->user(), $validated);

        return redirect()->route('shop.kyc')->with('success', 'Storefront created! Please upload your KYC documents to request shop verification.');
    }

    public function edit(Request $request, Shop $shop): Response
    {
        Gate::authorize('update', $shop);

        return Inertia::render('Shop/Edit', [
            'shop' => $shop->load(['settings', 'kycDocuments']),
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        Gate::authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'business_type' => 'nullable|string|in:cac_registered,sole_proprietorship',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pickup_radius_km' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'offers_home_delivery' => 'nullable|boolean',
            'offers_store_pickup' => 'nullable|boolean',
        ]);

        $this->shopService->update($shop, $validated);

        return back()->with('success', 'Shop updated successfully.');
    }

    public function show(string $slug): Response
    {
        $shop = Shop::where('slug', $slug)->with(['settings', 'categories', 'services', 'prices.category', 'prices.service'])->firstOrFail();
        $shop->has_active_subscription = $shop->hasActiveSubscription();

        return Inertia::render('Shop/Show', [
            'shop' => $shop,
        ]);
    }
}
