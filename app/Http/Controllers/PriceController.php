<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Price;
use App\Models\Service;
use App\Services\ShopContext;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class PriceController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->first();

        if (!$shop) {
            return Inertia::render('Shop/Pricing/Index', [
                'prices' => [],
                'categories' => [],
                'services' => [],
                'masterPrices' => [],
                'shop' => null,
            ]);
        }

        $categories = Category::withoutGlobalScopes()->where('shop_id', $shop->id)->where('is_active', true)->get();
        $services = Service::withoutGlobalScopes()->where('shop_id', $shop->id)->where('is_active', true)->get();
        $prices = Price::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->with(['category', 'service'])
            ->get();

        $masterPrices = Price::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->with(['category', 'service'])
            ->get();

        return Inertia::render('Shop/Pricing/Index', [
            'prices' => $prices,
            'categories' => $categories,
            'services' => $services,
            'masterPrices' => $masterPrices,
            'shop' => $shop,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'service_id' => 'required|exists:services,id',
            'amount' => 'required|numeric|min:0',
        ]);

        Price::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'category_id' => $validated['category_id'],
                'service_id' => $validated['service_id'],
            ],
            [
                'amount' => $validated['amount'],
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Pricing updated successfully.');
    }

    public function update(Request $request, Price $price): RedirectResponse
    {
        if (is_null($price->shop_id) && !$request->user()->hasRole(UserRole::SUPER_ADMIN->value)) {
            return back()->with('error', 'You cannot modify a platform master price template directly.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $price->update($validated);

        return back()->with('success', 'Price rule updated successfully.');
    }

    public function clone(Request $request, Price $price): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        // Find or clone category with clean name matching
        $masterCat = $price->category;
        $cleanCatName = trim(preg_replace('/\s*\(Master Template\)\s*/i', '', $masterCat->name));

        $shopCat = Category::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where(function ($q) use ($masterCat, $cleanCatName) {
                $q->where('name', $masterCat->name)
                  ->orWhere('name', $cleanCatName);
            })
            ->first();

        if (!$shopCat) {
            $shopCat = Category::create([
                'shop_id' => $shop->id,
                'name' => $cleanCatName,
                'icon' => $masterCat->icon,
                'sort_order' => $masterCat->sort_order,
                'is_active' => true,
            ]);
        }

        // Find or clone service with clean name matching
        $masterSvc = $price->service;
        $cleanSvcName = trim(preg_replace('/\s*\(Master Template\)\s*/i', '', $masterSvc->name));

        $shopSvc = Service::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where(function ($q) use ($masterSvc, $cleanSvcName) {
                $q->where('name', $masterSvc->name)
                  ->orWhere('name', $cleanSvcName);
            })
            ->first();

        if (!$shopSvc) {
            $shopSvc = Service::create([
                'shop_id' => $shop->id,
                'name' => $cleanSvcName,
                'description' => $masterSvc->description,
                'sort_order' => $masterSvc->sort_order,
                'is_active' => true,
            ]);
        }

        // Create or update pricing entry for current shop
        Price::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'category_id' => $shopCat->id,
                'service_id' => $shopSvc->id,
            ],
            [
                'amount' => $price->amount,
                'is_active' => true,
            ]
        );

        return back()->with('success', "Cloned pricing template for '{$shopCat->name} - {$shopSvc->name}' into your shop catalog.");
    }

    public function cloneAll(Request $request): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        DB::transaction(function () use ($shop) {
            $masterCategories = Category::withoutGlobalScopes()->whereNull('shop_id')->get();
            $categoryMap = [];
            foreach ($masterCategories as $mc) {
                $cleanCatName = trim(preg_replace('/\s*\(Master Template\)\s*/i', '', $mc->name));

                $sc = Category::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where(function ($q) use ($mc, $cleanCatName) {
                        $q->where('name', $mc->name)
                          ->orWhere('name', $cleanCatName);
                    })
                    ->first();
                if (!$sc) {
                    $sc = Category::create([
                        'shop_id' => $shop->id,
                        'name' => $cleanCatName,
                        'icon' => $mc->icon,
                        'sort_order' => $mc->sort_order,
                        'is_active' => true,
                    ]);
                }
                $categoryMap[$mc->id] = $sc->id;
            }

            $masterServices = Service::withoutGlobalScopes()->whereNull('shop_id')->get();
            $serviceMap = [];
            foreach ($masterServices as $ms) {
                $cleanSvcName = trim(preg_replace('/\s*\(Master Template\)\s*/i', '', $ms->name));

                $ss = Service::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where(function ($q) use ($ms, $cleanSvcName) {
                        $q->where('name', $ms->name)
                          ->orWhere('name', $cleanSvcName);
                    })
                    ->first();
                if (!$ss) {
                    $ss = Service::create([
                        'shop_id' => $shop->id,
                        'name' => $cleanSvcName,
                        'description' => $ms->description,
                        'sort_order' => $ms->sort_order,
                        'is_active' => true,
                    ]);
                }
                $serviceMap[$ms->id] = $ss->id;
            }

            $masterPrices = Price::withoutGlobalScopes()->whereNull('shop_id')->get();
            foreach ($masterPrices as $mp) {
                $targetCatId = $categoryMap[$mp->category_id] ?? null;
                $targetSvcId = $serviceMap[$mp->service_id] ?? null;
                if ($targetCatId && $targetSvcId) {
                    Price::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'category_id' => $targetCatId,
                            'service_id' => $targetSvcId,
                        ],
                        [
                            'amount' => $mp->amount,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Successfully cloned all master platform categories, services, and default pricing into your shop catalog!');
    }

    public function destroy(Price $price): RedirectResponse
    {
        $price->delete();

        return back()->with('success', 'Price entry deleted.');
    }
}
