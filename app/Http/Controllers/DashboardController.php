<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\RiderProfile;
use App\Models\Service;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            $totalSubRevenue = (float) Subscription::where('status', SubscriptionStatus::ACTIVE)->sum('amount');
            $grossOrderVolume = (float) Order::whereNotIn('status', [OrderStatus::CANCELLED->value, 'cancelled'])->sum('total_amount');
            $totalPlatformRevenue = $totalSubRevenue;

            // Monthly breakdown (Last 6 months)
            $labels = [];
            $subData = [];
            $orderData = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthDate = now()->subMonths($i);
                $monthLabel = $monthDate->format('M Y');
                $labels[] = $monthLabel;

                $subAmount = (float) Subscription::whereYear('created_at', $monthDate->year)
                    ->whereMonth('created_at', $monthDate->month)
                    ->sum('amount');
                $subData[] = $subAmount;

                $orderVol = (float) Order::whereYear('created_at', $monthDate->year)
                    ->whereMonth('created_at', $monthDate->month)
                    ->whereNotIn('status', [OrderStatus::CANCELLED->value, 'cancelled'])
                    ->sum('total_amount');
                $orderData[] = $orderVol;
            }

            $monthlyRevenue = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'SaaS Subscription Revenue (NGN)',
                        'data' => $subData,
                        'backgroundColor' => '#0284c7',
                        'borderColor' => '#38bdf8',
                    ],
                    [
                        'label' => 'Gross Order Settlement Volume',
                        'data' => $orderData,
                        'backgroundColor' => '#a855f7',
                        'borderColor' => '#c084fc',
                    ]
                ],
            ];

            // Order status distribution
            $statusCounts = Order::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $orderStatusDistribution = [
                'labels' => ['Completed', 'Cleaning in Progress', 'Picked Up', 'Pending Review', 'Cancelled'],
                'data' => [
                    $statusCounts['completed'] ?? 0,
                    $statusCounts['cleaning_in_progress'] ?? 0,
                    $statusCounts['garments_picked_up'] ?? 0,
                    $statusCounts['pending'] ?? 0,
                    $statusCounts['cancelled'] ?? 0,
                ],
                'colors' => ['#10b981', '#0284c7', '#38bdf8', '#f59e0b', '#f43f5e'],
            ];

            $topShops = Shop::withCount('orders')->latest()->take(5)->get();
            $shopLabels = $topShops->pluck('name')->toArray();
            $shopOrders = $topShops->pluck('orders_count')->toArray();

            $shopBreakdown = [
                'labels' => !empty($shopLabels) ? $shopLabels : ['No Active Shops'],
                'data' => !empty($shopOrders) ? $shopOrders : [0],
                'colors' => ['#0284c7', '#38bdf8', '#a855f7', '#10b981', '#f59e0b'],
            ];

            return Inertia::render('Dashboard/AdminDashboard', [
                'stats' => [
                    'total_revenue' => $totalPlatformRevenue,
                    'gross_order_volume' => $grossOrderVolume,
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

            $totalRevenue = 0;
            $totalOrders = 0;
            $salesData = [0, 0, 0, 0];
            $catLabels = ['No Garments Logged Yet'];
            $catData = [0];
            $catColors = ['#64748b'];

            if ($shop) {
                $totalRevenue = (float) Order::where('shop_id', $shop->id)
                    ->whereNotIn('status', [OrderStatus::CANCELLED->value, 'cancelled'])
                    ->sum('total_amount');

                $totalOrders = Order::where('shop_id', $shop->id)->count();

                // Weekly sales breakdown (Last 4 weeks)
                for ($w = 3; $w >= 0; $w--) {
                    $start = now()->subWeeks($w)->startOfWeek();
                    $end = now()->subWeeks($w)->endOfWeek();
                    $val = (float) Order::where('shop_id', $shop->id)
                        ->whereNotIn('status', [OrderStatus::CANCELLED->value, 'cancelled'])
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('total_amount');
                    $salesData[3 - $w] = $val;
                }

                // Category items breakdown
                $catCounts = OrderItem::whereHas('order', fn($q) => $q->where('shop_id', $shop->id)->whereNotIn('status', [OrderStatus::CANCELLED->value, 'cancelled']))
                    ->select('category_name', DB::raw('sum(quantity) as total_qty'))
                    ->groupBy('category_name')
                    ->orderByDesc('total_qty')
                    ->take(5)
                    ->get();

                if ($catCounts->isNotEmpty()) {
                    $catLabels = $catCounts->pluck('category_name')->toArray();
                    $catData = $catCounts->pluck('total_qty')->map(fn($v) => (int)$v)->toArray();
                    $catColors = ['#0284c7', '#a855f7', '#10b981', '#f59e0b', '#38bdf8'];
                }
            }

            $shopRevenueChart = [
                'labels' => ['3 Weeks Ago', '2 Weeks Ago', 'Last Week', 'This Week'],
                'datasets' => [
                    [
                        'label' => 'Shop Order Sales (NGN)',
                        'data' => $salesData,
                        'backgroundColor' => '#0284c7',
                        'borderColor' => '#38bdf8',
                    ],
                ],
            ];

            $categoryBreakdown = [
                'labels' => $catLabels,
                'data' => $catData,
                'colors' => $catColors,
            ];

            $shopActiveSub = $shop ? $shop->activeSubscription() : null;

            return Inertia::render('Dashboard/ShopDashboard', [
                'shop'               => $shop,
                'activeSubscription' => $shopActiveSub,
                'virtualAccount'     => $shop ? $shop->virtualAccount : null,
                'stats'              => [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
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

            $totalEarnings = (float) Order::where('rider_id', $user->id)
                ->where('status', OrderStatus::COMPLETED->value)
                ->sum('delivery_fee');

            $totalDeliveries = Order::where('rider_id', $user->id)
                ->where('status', OrderStatus::COMPLETED->value)
                ->count();

            // Last 7 Days earnings
            $daysLabels = [];
            $daysEarnings = [];
            for ($d = 6; $d >= 0; $d--) {
                $day = now()->subDays($d);
                $daysLabels[] = $day->format('D');
                $val = (float) Order::where('rider_id', $user->id)
                    ->whereDate('updated_at', $day->toDateString())
                    ->where('status', OrderStatus::COMPLETED->value)
                    ->sum('delivery_fee');
                $daysEarnings[] = $val;
            }

            $riderEarningsChart = [
                'labels' => $daysLabels,
                'datasets' => [
                    [
                        'label' => 'Delivery Earnings (NGN)',
                        'data' => $daysEarnings,
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#34d399',
                    ],
                ],
            ];

            $completedCount = Order::where('rider_id', $user->id)->where('status', 'completed')->count();
            $inTransitCount = Order::where('rider_id', $user->id)->where('status', 'out_for_delivery')->count();
            $assignedCount = Order::where('rider_id', $user->id)->whereIn('status', ['pickup_assigned', 'garments_picked_up'])->count();

            $deliveryStatusChart = [
                'labels' => ['Completed Deliveries', 'In Transit', 'Picked Up / Assigned'],
                'data' => [$completedCount, $inTransitCount, $assignedCount],
                'colors' => ['#10b981', '#0284c7', '#f59e0b'],
            ];

            $riderActiveSub = Subscription::where('user_id', $user->id)
                ->where('role', 'rider')
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where('ends_at', '>', now())
                ->latest()
                ->first();

            return Inertia::render('Dashboard/RiderDashboard', [
                'profile' => $profile ? $profile->load('kycDocuments') : null,
                'activeSubscription' => $riderActiveSub,
                'stats' => [
                    'total_earnings' => $totalEarnings,
                    'total_deliveries' => $totalDeliveries,
                    'rating' => 4.9,
                ],
                'rider_earnings_chart' => $riderEarningsChart,
                'delivery_status_chart' => $deliveryStatusChart,
            ]);
        }

        $userLat = (float) $user->latitude;
        $userLng = (float) $user->longitude;
        $userCity = $user->city;

        if ($userLat && $userLng) {
            $shops = Shop::where('status', 'active')
                ->select('*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_km',
                    [$userLat, $userLng, $userLat]
                )
                ->get()
                ->map(function ($shop) {
                    $dist = round((float) $shop->distance_km, 1);
                    $shop->distance_km = $dist;
                    $shop->delivers_to_user = ($dist <= (float) $shop->pickup_radius_km);
                    return $shop;
                })
                ->sortBy('distance_km')
                ->values();
        } else if (!empty($userCity)) {
            $shops = Shop::where('status', 'active')
                ->get()
                ->map(function ($shop) use ($userCity) {
                    $isSameCity = (bool) preg_match("/" . preg_quote($userCity, '/') . "/i", $shop->address);
                    $shop->distance_km = null;
                    $shop->delivers_to_user = $isSameCity;
                    return $shop;
                });
        } else {
            $shops = Shop::where('status', 'active')->latest()->get()->map(function ($shop) {
                $shop->distance_km = null;
                $shop->delivers_to_user = true;
                return $shop;
            });
        }

        return Inertia::render('Dashboard/CustomerDashboard', [
            'shops' => $shops,
            'customerLocation' => [
                'address' => $user->address,
                'city' => $user->city,
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
            ],
        ]);
    }
}
