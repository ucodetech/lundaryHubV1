<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\ShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->first();

        $services = $shop
            ? Service::withoutGlobalScopes()->where('shop_id', $shop->id)->orderBy('sort_order')->get()
            : collect();

        $masterServices = Service::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Shop/Services/Index', [
            'services' => $services,
            'masterServices' => $masterServices,
            'shop' => $shop,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        Service::create([
            'shop_id' => $shop->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Service added successfully.');
    }

    public function clone(Request $request, Service $service): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $existing = Service::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('name', $service->name)
            ->first();

        if ($existing) {
            return back()->with('error', "Service '{$service->name}' already exists in your shop catalog.");
        }

        Service::create([
            'shop_id' => $shop->id,
            'name' => $service->name,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
            'is_active' => true,
        ]);

        return back()->with('success', "Master template service '{$service->name}' cloned to your shop.");
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return back()->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}
