<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'defaultRole' => $request->query('role', 'customer'),
            'initialReferralCode' => $request->query('ref', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|string|max:20|unique:'.User::class,
            'role' => 'required|string|in:customer,shop_owner,rider',
            'referral_code' => 'nullable|string|max:50',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $roleEnum = UserRole::from($request->role);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'referral_code' => $request->phone, // Set user's own phone number as their unique referral code!
            'role' => $roleEnum,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'email_verified_at' => now(), // Auto-verify email so user can access app immediately
            'phone_verified_at' => now(), // Auto-pass phone verification
        ]);

        $user->assignRole($roleEnum->value);

        if ($request->filled('referral_code')) {
            \App\Services\ReferralService::recordRegistration($user, $request->referral_code);
        }

        // Real-time Push Notification to Admin
        try {
            $roleLabel = $roleEnum->label();
            $notificationType = $roleEnum === UserRole::RIDER ? 'rider_registered' : 'user_registered';
            event(new \App\Events\AdminNotificationEvent(
                title: "New {$roleLabel} Registered!",
                message: "{$user->first_name} {$user->last_name} ({$user->phone}) registered as {$roleLabel}.",
                type: $notificationType,
                url: '/admin/users'
            ));

            // Web Push notification to Super Admins
            $superAdmins = User::role(UserRole::SUPER_ADMIN->value)->get();
            foreach ($superAdmins as $adminUser) {
                $adminUser->notify(new \App\Notifications\WebPushAlert(
                    title: "🔔 New {$roleLabel} Joined",
                    body: "{$user->first_name} {$user->last_name} registered ({$user->email}).",
                    url: '/admin/users'
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Admin registration notification notice: " . $e->getMessage());
        }

        Auth::login($user);

        if ($roleEnum === UserRole::SHOP_OWNER) {
            return redirect()->route('shop.create');
        }

        if ($roleEnum === UserRole::RIDER) {
            return redirect()->route('rider.profile');
        }

        return redirect()->route('dashboard');
    }
}
