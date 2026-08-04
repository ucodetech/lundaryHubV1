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
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'shops' => \App\Models\Shop::where('status', 'active')->take(6)->get(),
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
    });

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
        Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/subscription-plans', [\App\Http\Controllers\AdminSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::put('/subscription-plans/{plan}', [\App\Http\Controllers\AdminSubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
    });
});

require __DIR__.'/auth.php';
