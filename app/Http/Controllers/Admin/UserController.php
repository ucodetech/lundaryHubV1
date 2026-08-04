<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'is_active']);

        $query = User::latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        $statusLabel = $user->is_active ? 'activated' : 'suspended';

        return back()->with('success', "User '{$user->name}' has been {$statusLabel}.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'role' => 'required|string|in:super_admin,support,shop_owner,rider,customer',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        try {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role']]);
            $user->assignRole($validated['role']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Spatie role assignment notice: " . $e->getMessage());
        }

        if ($validated['role'] === 'rider') {
            \App\Models\RiderProfile::create([
                'user_id' => $user->id,
                'verification_status' => \App\Enums\KycStatus::APPROVED,
                'vehicle_type' => 'Motorcycle',
            ]);
        }

        return back()->with('success', "🎉 System user '{$user->first_name} {$user->last_name}' ({$user->role}) created successfully!");
    }
}
