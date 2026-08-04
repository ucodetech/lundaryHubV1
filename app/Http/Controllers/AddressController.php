<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['addresses' => $addresses]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50', // Home, Office, Apartment
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);

        $user = $request->user();

        if (!empty($validated['is_default'])) {
            Address::where('user_id', $user->id)->update(['is_default' => false]);
        }

        Address::create([
            'user_id' => $user->id,
            'label' => $validated['label'],
            'address_line_1' => $validated['address_line_1'],
            'city' => $validated['city'],
            'state' => $validated['state'] ?? 'Lagos',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return back()->with('success', "📍 Address '{$validated['label']}' saved successfully!");
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        if ($address->user_id === $request->user()->id) {
            $address->delete();
        }

        return back()->with('success', 'Address removed from address book.');
    }
}
