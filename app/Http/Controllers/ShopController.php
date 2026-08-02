<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\ShopService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pickup_radius_km' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $shop = $this->shopService->create($request->user(), $validated);

        return redirect()->route('dashboard')->with('success', 'Shop created successfully! Pending admin approval.');
    }

    public function edit(Shop $shop): Response
    {
        $this->authorize('update', $shop);

        return Inertia::render('Shop/Edit', [
            'shop' => $shop->load('settings'),
        ]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $this->authorize('update', $shop);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pickup_radius_km' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        $this->shopService->update($shop, $validated);

        return back()->with('success', 'Shop updated successfully.');
    }

    public function show(string $slug): Response
    {
        $shop = Shop::where('slug', $slug)->with(['settings', 'categories', 'services', 'prices.category', 'prices.service'])->firstOrFail();

        return Inertia::render('Shop/Show', [
            'shop' => $shop,
        ]);
    }
}
