<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\ShopContext;
use App\Enums\UserRole;
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
        $user = $request->user();
        $shop = app(ShopContext::class)->get() ?? $user?->ownedShops()?->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_master' => 'nullable|boolean',
        ]);

        $shopId = ($user->hasRole(UserRole::SUPER_ADMIN->value) && ($request->is_master || !$shop)) ? null : $shop?->id;

        Service::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', $shopId ? 'Service added successfully.' : 'Platform master service template created successfully.');
    }

    public function clone(Request $request, Service $service): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $cleanName = trim(preg_replace('/\s*\(Master Template\)\s*/i', '', $service->name));

        $existing = Service::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where(function ($q) use ($service, $cleanName) {
                $q->where('name', $service->name)
                  ->orWhere('name', $cleanName);
            })
            ->first();

        if ($existing) {
            return back()->with('error', "Service '{$cleanName}' already exists in your shop catalog.");
        }

        Service::create([
            'shop_id' => $shop->id,
            'name' => $cleanName,
            'description' => $service->description,
            'sort_order' => $service->sort_order,
            'is_active' => true,
        ]);

        return back()->with('success', "Master template service '{$cleanName}' cloned to your shop.");
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        if (is_null($service->shop_id) && !$request->user()->hasRole(UserRole::SUPER_ADMIN->value)) {
            return back()->with('error', 'You cannot modify a platform master service template directly.');
        }

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
