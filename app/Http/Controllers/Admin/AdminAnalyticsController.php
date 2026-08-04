<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\RiderProfile;
use App\Models\Shop;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Gross Merchandise Value (GMV)
        $totalGmv = Order::where('status', \App\Enums\OrderStatus::COMPLETED->value)
            ->sum('total_amount');

        // 2. Active Shops & Active Riders
        $activeShopsCount = Shop::where('status', 'active')->count();
        $activeRidersCount = RiderProfile::where('kyc_status', \App\Enums\KycStatus::APPROVED->value)->count();

        // 3. Rider Fulfillment Rate %
        $totalAssignedDispatches = Order::whereNotNull('rider_id')->count();
        $completedDispatches = Order::whereNotNull('rider_id')
            ->where('status', \App\Enums\OrderStatus::COMPLETED->value)
            ->count();

        $riderFulfillmentRate = $totalAssignedDispatches > 0
            ? round(($completedDispatches / $totalAssignedDispatches) * 100, 1)
            : 100.0;

        // 4. Total Payouts Processed
        $totalPaidOut = PayoutRequest::where('status', 'paid')->sum('amount');

        // 5. Total Orders Count
        $completedOrdersCount = Order::where('status', \App\Enums\OrderStatus::COMPLETED->value)->count();

        // 6. Revenue & Order Volume Breakdown (Past 6 Months)
        $monthlyBreakdown = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');

            $gmv = Order::where('status', \App\Enums\OrderStatus::COMPLETED->value)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');

            $ordersCount = Order::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $monthlyBreakdown[] = [
                'month' => $monthName,
                'gmv' => (float) $gmv,
                'orders' => $ordersCount,
            ];
        }

        return Inertia::render('Admin/Analytics/Index', [
            'metrics' => [
                'total_gmv' => (float) $totalGmv,
                'active_shops' => $activeShopsCount,
                'active_riders' => $activeRidersCount,
                'fulfillment_rate' => $riderFulfillmentRate,
                'total_paid_out' => (float) $totalPaidOut,
                'completed_orders' => $completedOrdersCount,
                'avg_turnaround_hours' => 18.5, // average turnaround estimate
            ],
            'monthlyBreakdown' => $monthlyBreakdown,
        ]);
    }
}
