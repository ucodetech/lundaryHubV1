<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerLocationController extends Controller
{
    public function updateLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address'   => 'required|string|max:500',
            'city'      => 'nullable|string|max:255',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = $request->user();
        $user->update([
            'address'   => $validated['address'],
            'city'      => $validated['city'] ?? $user->city,
            'latitude'  => $validated['latitude'] ?? $user->latitude,
            'longitude' => $validated['longitude'] ?? $user->longitude,
        ]);

        Address::updateOrCreate(
            ['user_id' => $user->id, 'is_default' => true],
            [
                'label'          => 'Primary Delivery Location',
                'address_line_1' => $validated['address'],
                'city'           => $validated['city'] ?? 'Lagos',
                'latitude'       => $validated['latitude'] ?? null,
                'longitude'      => $validated['longitude'] ?? null,
            ]
        );

        return back()->with('success', '📍 Delivery location updated! Dry cleaners in your area are now streamlined.');
    }
}
