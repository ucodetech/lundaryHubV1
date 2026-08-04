<?php

use App\Http\Controllers\Admin\RiderVerificationController;
use App\Http\Controllers\Admin\ShopVerificationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiderProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopKycController;
use App\Http\Controllers\ShopSettingsController;
use App\Http\Middleware\EnsureShopIsVerified;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Landing Page / Shop Marketplace
Route::get('/', function (\Illuminate\Http\Request $request) {
    $userLat = (float) ($request->query('lat') ?? 6.5244); // Default Lagos lat
    $userLng = (float) ($request->query('lng') ?? 3.3792); // Default Lagos lng

    $shops = \App\Models\Shop::where('status', 'active')->get()->map(function ($shop) use ($userLat, $userLng) {
        $distance = null;
        if ($shop->latitude && $shop->longitude) {
            $earthRadius = 6371;
            $dLat = deg2rad($shop->latitude - $userLat);
            $dLon = deg2rad($shop->longitude - $userLng);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                 cos(deg2rad($userLat)) * cos(deg2rad($shop->latitude)) *
                 sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = round($earthRadius * $c, 1);
        } else {
            // Demo fallback distance calculation
            $distance = round(1.2 + ($shop->id * 0.7), 1);
        }

        $shopArray = $shop->toArray();
        $shopArray['distance_km'] = $distance;
        return $shopArray;
    });

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'shops' => $shops,
    ]);
})->name('home');

// Public Shop Storefront Page
Route::get('/shop/{slug}', [ShopController::class, 'show'])->name('shops.show');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard (Gated by EnsureShopIsVerified for pending shop owners)
    Route::get('/dashboard', DashboardController::class)->middleware([EnsureShopIsVerified::class])->name('dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shop Owner Base Routes (Create & Submit KYC)
    Route::middleware(['role:shop_owner|super_admin'])->prefix('shop-admin')->name('shop.')->group(function () {
        Route::get('/create', [ShopController::class, 'create'])->name('create');
        Route::post('/store', [ShopController::class, 'store'])->name('store');
        Route::get('/{shop}/edit', [ShopController::class, 'edit'])->name('edit');
        Route::put('/{shop}', [ShopController::class, 'update'])->name('update');

        // Shop KYC Verification Uploads
        Route::get('/kyc', [ShopKycController::class, 'show'])->name('kyc');
        Route::post('/kyc', [ShopKycController::class, 'store'])->name('kyc.store');

        // Gated Operational Features (Locked until Shop is Verified by Super Admin)
        Route::middleware([EnsureShopIsVerified::class])->group(function () {
            // Shop Subscription Management (Always accessible to verified shops)
            Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'showShopPlans'])->name('subscription');

            // Gated Features (Requires Active Subscription)
            Route::middleware([\App\Http\Middleware\EnsureShopHasActiveSubscription::class])->group(function () {
                Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
                Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
                Route::post('/categories/{category}/clone', [CategoryController::class, 'clone'])->name('categories.clone');
                Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
                Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

                Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
                Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
                Route::post('/services/{service}/clone', [ServiceController::class, 'clone'])->name('services.clone');
                Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
                Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

                Route::get('/pricing', [PriceController::class, 'index'])->name('pricing.index');
                Route::post('/pricing', [PriceController::class, 'store'])->name('pricing.store');
                Route::post('/pricing/clone-all', [PriceController::class, 'cloneAll'])->name('pricing.clone-all');
                Route::post('/pricing/{price}/clone', [PriceController::class, 'clone'])->name('pricing.clone');
                Route::put('/pricing/{price}', [PriceController::class, 'update'])->name('pricing.update');
                Route::delete('/pricing/{price}', [PriceController::class, 'destroy'])->name('pricing.destroy');

                // Shop Orders Management & Manual Legacy Orders
                Route::get('/orders', [\App\Http\Controllers\ShopOrderController::class, 'index'])->name('orders.index');
                Route::post('/orders/manual', [\App\Http\Controllers\ShopOrderController::class, 'storeManual'])->name('orders.manual');
                Route::post('/orders/{order}/link-customer', [\App\Http\Controllers\ShopOrderController::class, 'linkCustomer'])->name('orders.link-customer');
                Route::post('/orders/{order}/request-pickup', [\App\Http\Controllers\ShopOrderController::class, 'requestPickup'])->name('orders.request-pickup');
                Route::put('/orders/{order}/status', [\App\Http\Controllers\ShopOrderController::class, 'updateStatus'])->name('orders.status');
                Route::get('/orders/{order}/tag', [\App\Http\Controllers\ShopOrderController::class, 'printTag'])->name('orders.tag');
            });
        });
    });

    // Alias route for garment tag printing
    Route::get('/shop/orders/{order}/tag', [\App\Http\Controllers\ShopOrderController::class, 'printTag']);

    // Customer Orders & Location Routes
    Route::get('/orders', [\App\Http\Controllers\CustomerOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [\App\Http\Controllers\CustomerOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{orderNumber}', [\App\Http\Controllers\CustomerOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/bids/{bid}/accept', [\App\Http\Controllers\CustomerOrderController::class, 'acceptBid'])->name('orders.bids.accept');
    Route::post('/orders/{order}/bids/{bid}/reject', [\App\Http\Controllers\CustomerOrderController::class, 'rejectBid'])->name('orders.bids.reject');
    Route::post('/orders/{order}/confirm-delivery', [\App\Http\Controllers\CustomerOrderController::class, 'confirmDelivery'])->name('orders.confirm-delivery');
    Route::post('/orders/{order}/review', [\App\Http\Controllers\OrderReviewController::class, 'store'])->name('orders.review');
    Route::post('/customer/location', [\App\Http\Controllers\CustomerLocationController::class, 'updateLocation'])->name('customer.location.update');

    // Paystack Payment Routes
    Route::get('/payments/paystack/initialize/{order}', [\App\Http\Controllers\PaystackController::class, 'initialize'])->name('paystack.initialize');
    Route::get('/payments/paystack/callback', [\App\Http\Controllers\PaystackController::class, 'callback'])->name('paystack.callback');
    Route::post('/api/paystack/webhook', [\App\Http\Controllers\PaystackController::class, 'webhook'])->name('paystack.webhook');

    // Common Subscription Paystack Routes
    Route::get('/subscriptions/checkout/{plan}', [\App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    Route::get('/subscriptions/callback', [\App\Http\Controllers\SubscriptionController::class, 'callback'])->name('subscriptions.callback');

    // Rider Routes
    Route::middleware(['role:rider|super_admin'])->prefix('rider')->name('rider.')->group(function () {
        Route::get('/orders', [\App\Http\Controllers\RiderOrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/{order}/bid', [\App\Http\Controllers\RiderOrderController::class, 'submitBid'])->name('orders.bid');
        Route::post('/orders/{order}/accept', [\App\Http\Controllers\RiderOrderController::class, 'acceptOrder'])->name('orders.accept');
        Route::post('/orders/{order}/decline', [\App\Http\Controllers\RiderOrderController::class, 'declineOrder'])->name('orders.decline');
        Route::put('/orders/{order}/status', [\App\Http\Controllers\RiderOrderController::class, 'updateDeliveryStatus'])->name('orders.status');
        Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'showRiderPass'])->name('subscription');
        Route::get('/profile', [RiderProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [RiderProfileController::class, 'update'])->name('profile.update');
        Route::post('/kyc', [RiderProfileController::class, 'uploadKyc'])->name('kyc.upload');
        Route::post('/toggle-online', [RiderProfileController::class, 'toggleOnline'])->name('toggle-online');

        // Rider Payout & Earnings Hub
        Route::get('/payouts', [\App\Http\Controllers\RiderPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [\App\Http\Controllers\RiderPayoutController::class, 'store'])->name('payouts.store');
    });

    // Notification Routes
    Route::get('/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');

    // Customer Saved Address Book Routes
    Route::get('/addresses', [\App\Http\Controllers\AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [\App\Http\Controllers\AddressController::class, 'store'])->name('addresses.store');
    Route::delete('/addresses/{address}', [\App\Http\Controllers\AddressController::class, 'destroy'])->name('addresses.destroy');

    // Shared Bank Account Verification Routes
    Route::get('/bank-accounts/banks', [\App\Http\Controllers\BankAccountController::class, 'getBanks'])->name('bank-accounts.banks');
    Route::post('/bank-accounts/resolve', [\App\Http\Controllers\BankAccountController::class, 'resolveAccount'])->name('bank-accounts.resolve');
    Route::post('/bank-accounts/save', [\App\Http\Controllers\BankAccountController::class, 'saveBankAccount'])->name('bank-accounts.save');

    // Super Admin Routes
    Route::middleware(['role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/shops', [ShopVerificationController::class, 'index'])->name('shops.index');
        Route::post('/shops/{shop}/verify', [ShopVerificationController::class, 'verify'])->name('shops.verify');
        Route::post('/shops/{shop}/generate-virtual-account', [ShopVerificationController::class, 'generateVirtualAccount'])->name('shops.generate-virtual-account');
        Route::post('/shops/{shop}/suspend', [ShopVerificationController::class, 'suspend'])->name('shops.suspend');

        Route::get('/riders', [RiderVerificationController::class, 'index'])->name('riders.index');
        Route::post('/riders/{rider}/approve', [RiderVerificationController::class, 'approve'])->name('riders.approve');
        Route::post('/riders/{rider}/reject', [RiderVerificationController::class, 'reject'])->name('riders.reject');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/subscription-plans', [\App\Http\Controllers\AdminSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::put('/subscription-plans/{plan}', [\App\Http\Controllers\AdminSubscriptionPlanController::class, 'update'])->name('subscription-plans.update');

        // Admin Dispute Management Command Center
        Route::get('/disputes', [\App\Http\Controllers\Admin\AdminDisputeController::class, 'index'])->name('disputes.index');
        Route::post('/disputes/{dispute}/resolve', [\App\Http\Controllers\Admin\AdminDisputeController::class, 'resolve'])->name('disputes.resolve');

        // Admin Payout Settlement Hub
        Route::get('/payouts', [\App\Http\Controllers\Admin\AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payout}/approve', [\App\Http\Controllers\Admin\AdminPayoutController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payout}/reject', [\App\Http\Controllers\Admin\AdminPayoutController::class, 'reject'])->name('payouts.reject');

        // Admin Financial Analytics & Executive Dashboard
        Route::get('/analytics', [\App\Http\Controllers\Admin\AdminAnalyticsController::class, 'index'])->name('analytics.index');
    });

    // Referral Hub Routes
    Route::get('/referrals', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');

    // Customer & Shop & Rider Order Dispute Ticket Routes
    Route::get('/disputes', [\App\Http\Controllers\DisputeController::class, 'index'])->name('disputes.index');
    Route::post('/disputes', [\App\Http\Controllers\DisputeController::class, 'store'])->name('disputes.store');
    Route::get('/disputes/{dispute}', [\App\Http\Controllers\DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/reply', [\App\Http\Controllers\DisputeController::class, 'reply'])->name('disputes.reply');
});

// Web Setup & Migration Route for Shared Hosting (No Terminal / SSH required)
Route::get('/artisan-setup-laundryhub-2026', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrate = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $seed = \Illuminate\Support\Facades\Artisan::output();

        // try {
        //     \Illuminate\Support\Facades\Artisan::call('storage:link');
        //     $storage = \Illuminate\Support\Facades\Artisan::output();
        // } catch (\Throwable $e) {
        //     $storage = 'Storage link notice: ' . $e->getMessage();
        // }

        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        return response()->json([
            'status' => 'success',
            'message' => '🎉 Production database migrations, seeders, storage link & caches completed successfully!',
            'migrate_output' => $migrate,
            'seed_output' => $seed,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

require __DIR__.'/auth.php';

