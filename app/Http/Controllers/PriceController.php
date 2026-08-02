<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Price;
use App\Models\Service;
use App\Services\ShopContext;
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

    public function clone(Request $request, Price $price): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        // Find or clone category
        $masterCat = $price->category;
        $shopCat = Category::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('name', $masterCat->name)
            ->first();

        if (!$shopCat) {
            $shopCat = Category::create([
                'shop_id' => $shop->id,
                'name' => $masterCat->name,
                'icon' => $masterCat->icon,
                'sort_order' => $masterCat->sort_order,
                'is_active' => true,
            ]);
        }

        // Find or clone service
        $masterSvc = $price->service;
        $shopSvc = Service::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('name', $masterSvc->name)
            ->first();

        if (!$shopSvc) {
            $shopSvc = Service::create([
                'shop_id' => $shop->id,
                'name' => $masterSvc->name,
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
                $sc = Category::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('name', $mc->name)
                    ->first();
                if (!$sc) {
                    $sc = Category::create([
                        'shop_id' => $shop->id,
                        'name' => $mc->name,
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
                $ss = Service::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('name', $ms->name)
                    ->first();
                if (!$ss) {
                    $ss = Service::create([
                        'shop_id' => $shop->id,
                        'name' => $ms->name,
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
