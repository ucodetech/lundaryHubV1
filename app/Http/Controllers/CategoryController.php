<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ShopContext;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->first();

        $categories = $shop
            ? Category::withoutGlobalScopes()->where('shop_id', $shop->id)->orderBy('sort_order')->get()
            : collect();

        $masterCategories = Category::withoutGlobalScopes()
            ->whereNull('shop_id')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Shop/Categories/Index', [
            'categories' => $categories,
            'masterCategories' => $masterCategories,
            'shop' => $shop,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $shop = app(ShopContext::class)->get() ?? $user?->ownedShops()?->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_master' => 'nullable|boolean',
        ]);

        $shopId = ($user->hasRole(UserRole::SUPER_ADMIN->value) && ($request->is_master || !$shop)) ? null : $shop?->id;

        Category::create([
            'shop_id' => $shopId,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', $shopId ? 'Category added successfully.' : 'Platform master category template created successfully.');
    }

    public function clone(Request $request, Category $category): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $existing = Category::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('name', $category->name)
            ->first();

        if ($existing) {
            return back()->with('error', "Category '{$category->name}' already exists in your shop catalog.");
        }

        Category::create([
            'shop_id' => $shop->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => true,
        ]);

        return back()->with('success', "Master template category '{$category->name}' cloned to your shop.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        if (is_null($category->shop_id) && !$request->user()->hasRole(UserRole::SUPER_ADMIN->value)) {
            return back()->with('error', 'You cannot modify a platform master category template directly.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
