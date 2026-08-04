<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSubscriptionPlanController extends Controller
{
    public function index(): Response
    {
        $plans = SubscriptionPlan::latest()->get();

        return Inertia::render('Admin/SubscriptionPlans/Index', [
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'interval_days' => 'required|integer|min:1',
            'order_limit' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return back()->with('success', "Subscription plan '{$plan->name}' updated successfully!");
    }
}
