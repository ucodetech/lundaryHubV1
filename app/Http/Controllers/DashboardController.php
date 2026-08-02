<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Price;
use App\Models\RiderProfile;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            // Super Admin Analytics
            $monthlyRevenue = [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                'datasets' => [
                    [
                        'label' => 'Platform Revenue (NGN)',
                        'data' => [450000, 680000, 890000, 1120000, 1450000, 1820000, 2100000, 2650000],
                        'backgroundColor' => '#0284c7',
                        'borderColor' => '#38bdf8',
                    ],
                    [
                        'label' => 'Gross Order Volume',
                        'data' => [320000, 500000, 620000, 850000, 1100000, 1350000, 1600000, 1980000],
                        'backgroundColor' => '#a855f7',
                        'borderColor' => '#c084fc',
                    ]
                ],
            ];

            $shopBreakdown = [
                'labels' => ['Sparkle Dry Cleaners', 'CleanExpress VI', 'Royal Fabrics Ikoyi', 'Lagos Premium Laundry'],
                'data' => [1450000, 620000, 380000, 200000],
                'colors' => ['#0284c7', '#38bdf8', '#a855f7', '#10b981'],
            ];

            $orderStatusDistribution = [
                'labels' => ['Completed', 'In Cleaning', 'Picked Up', 'Pending Approval', 'Cancelled'],
                'data' => [62, 18, 12, 5, 3],
                'colors' => ['#10b981', '#0284c7', '#38bdf8', '#f59e0b', '#f43f5e'],
            ];

            return Inertia::render('Dashboard/AdminDashboard', [
                'stats' => [
                    'total_revenue' => 2650000,
                    'total_customers' => User::where('role', UserRole::CUSTOMER->value)->count(),
                    'total_shops' => Shop::count(),
                    'pending_shops' => Shop::where('status', 'pending')->count(),
                    'total_riders' => RiderProfile::count(),
                    'pending_kyc' => RiderProfile::where('kyc_status', 'pending')->count(),
                ],
                'monthly_revenue_chart' => $monthlyRevenue,
                'shop_breakdown_chart' => $shopBreakdown,
                'order_status_chart' => $orderStatusDistribution,
                'recent_shops' => Shop::with('owner')->latest()->take(5)->get(),
                'recent_riders' => RiderProfile::with('user')->latest()->take(5)->get(),
            ]);
        }

        if ($user->hasRole(UserRole::SHOP_OWNER->value)) {
            $shop = $user->ownedShops()->first();

            $shopRevenueChart = [
                'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                'datasets' => [
                    [
                        'label' => 'Shop Sales (NGN)',
                        'data' => [180000, 260000, 340000, 420000],
                        'backgroundColor' => '#0284c7',
                        'borderColor' => '#38bdf8',
                    ],
                ],
            ];

            $categoryBreakdown = [
                'labels' => ['Shirts', 'Suits', 'Native Wear', 'Duvets', 'Jeans'],
                'data' => [35, 25, 20, 12, 8],
                'colors' => ['#0284c7', '#a855f7', '#10b981', '#f59e0b', '#38bdf8'],
            ];

            return Inertia::render('Dashboard/ShopDashboard', [
                'shop' => $shop,
                'stats' => [
                    'total_revenue' => 1200000,
                    'total_orders' => 142,
                    'total_categories' => $shop ? Category::withoutGlobalScopes()->where('shop_id', $shop->id)->count() : 0,
                    'total_services' => $shop ? Service::withoutGlobalScopes()->where('shop_id', $shop->id)->count() : 0,
                    'total_prices' => $shop ? Price::withoutGlobalScopes()->where('shop_id', $shop->id)->count() : 0,
                ],
                'shop_revenue_chart' => $shopRevenueChart,
                'category_breakdown_chart' => $categoryBreakdown,
            ]);
        }

        if ($user->hasRole(UserRole::RIDER->value)) {
            $profile = $user->riderProfile;

            $riderEarningsChart = [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'datasets' => [
                    [
                        'label' => 'Delivery Earnings (NGN)',
                        'data' => [8500, 12000, 9500, 14000, 18500, 22000, 16000],
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#34d399',
                    ],
                ],
            ];

            $deliveryStatusChart = [
                'labels' => ['Completed Deliveries', 'In Transit', 'Picked Up'],
                'data' => [48, 6, 2],
                'colors' => ['#10b981', '#0284c7', '#f59e0b'],
            ];

            return Inertia::render('Dashboard/RiderDashboard', [
                'profile' => $profile ? $profile->load('kycDocuments') : null,
                'stats' => [
                    'total_earnings' => 100500,
                    'total_deliveries' => 56,
                    'rating' => 4.9,
                ],
                'rider_earnings_chart' => $riderEarningsChart,
                'delivery_status_chart' => $deliveryStatusChart,
            ]);
        }

        return Inertia::render('Dashboard/CustomerDashboard', [
            'shops' => Shop::where('status', 'active')->latest()->take(6)->get(),
        ]);
    }
}
