<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionStatus;
use App\Events\BidSubmitted;
use App\Events\OrderAcceptedByRider;
use App\Models\DispatchBid;
use App\Models\Order;
use App\Models\Subscription;
use App\Services\RiderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiderOrderController extends Controller
{
    public function __construct(protected RiderService $riderService)
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'rider')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '>', now())
            ->first();

        $availableOrders = Order::whereNull('rider_id')
            ->where('fulfillment_type', FulfillmentType::HOME_DELIVERY)
            ->where('is_dispatch_requested', true)
            ->whereNotIn('status', [OrderStatus::COMPLETED, OrderStatus::CANCELLED])
            ->whereDoesntHave('bids', function ($q) use ($user) {
                $q->where('rider_id', $user->id)->where('status', 'rejected_by_rider');
            })
            ->with(['shop', 'customer', 'items.category', 'items.service', 'bids' => function ($q) use ($user) {
                $q->where('rider_id', $user->id);
            }])
            ->latest()
            ->get()
            ->map(function ($order) use ($user) {
                $myBid = $order->bids->first();
                $isReturnDelivery = in_array($order->status->value ?? $order->status, ['ready_for_delivery', 'out_for_delivery']);

                $customerName = $order->is_legacy ? $order->legacy_customer_name : ($order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : 'Customer');
                $customerPhone = $order->is_legacy ? $order->legacy_customer_phone : ($order->customer->phone ?? 'N/A');
                $customerAddr = $order->pickup_address ?? $order->delivery_address ?? $order->shop->address;

                $shopName = $order->shop->name ?? 'Laundry Shop';
                $shopAddr = $order->shop->address ?? 'N/A';
                $shopPhone = $order->shop->phone ?? 'N/A';

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'fulfillment_type' => $order->fulfillment_type->value,
                    'is_return_delivery' => $isReturnDelivery,
                    'phase_label' => $isReturnDelivery ? '📦 Clean Clothes Return' : '🧺 Initial Garment Pickup',
                    'origin_title' => $isReturnDelivery ? '🏬 1. Pickup Origin (Laundry Shop)' : '📍 1. Pickup Origin (Customer Address)',
                    'origin_name' => $isReturnDelivery ? $shopName : $customerName,
                    'origin_address' => $isReturnDelivery ? $shopAddr : $customerAddr,
                    'origin_phone' => $isReturnDelivery ? $shopPhone : $customerPhone,
                    'destination_title' => $isReturnDelivery ? '📍 2. Drop-off Destination (Customer Home)' : '🏬 2. Drop-off Destination (Laundry Shop)',
                    'destination_name' => $isReturnDelivery ? $customerName : $shopName,
                    'destination_address' => $isReturnDelivery ? ($order->delivery_address ?? $customerAddr) : $shopAddr,
                    'destination_phone' => $isReturnDelivery ? $customerPhone : $shopPhone,
                    'delivery_fee' => number_format((float) $order->delivery_fee, 2),
                    'total_amount' => number_format((float) $order->total_amount, 2),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->diffForHumans(),
                    'my_bid' => $myBid ? [
                        'id' => $myBid->id,
                        'amount' => (float) $myBid->amount,
                        'note' => $myBid->note,
                        'status' => $myBid->status,
                    ] : null,
                ];
            });

        $myActiveDeliveries = Order::where('rider_id', $user->id)
            ->whereNotIn('status', [OrderStatus::COMPLETED, OrderStatus::CANCELLED])
            ->with(['shop', 'customer', 'items.category', 'items.service'])
            ->latest()
            ->get()
            ->map(function ($order) {
                $isReturnDelivery = in_array($order->status->value ?? $order->status, ['ready_for_delivery', 'out_for_delivery']);

                $customerName = $order->is_legacy ? $order->legacy_customer_name : ($order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : 'Customer');
                $customerPhone = $order->is_legacy ? $order->legacy_customer_phone : ($order->customer->phone ?? 'N/A');
                $customerAddr = $order->pickup_address ?? $order->delivery_address ?? $order->shop->address;

                $shopName = $order->shop->name ?? 'Laundry Shop';
                $shopAddr = $order->shop->address ?? 'N/A';
                $shopPhone = $order->shop->phone ?? 'N/A';

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'fulfillment_type' => $order->fulfillment_type->value,
                    'is_return_delivery' => $isReturnDelivery,
                    'phase_label' => $isReturnDelivery ? '📦 Clean Clothes Return' : '🧺 Initial Garment Pickup',
                    'origin_title' => $isReturnDelivery ? '🏬 1. Pickup Origin (Laundry Shop)' : '📍 1. Pickup Origin (Customer Address)',
                    'origin_name' => $isReturnDelivery ? $shopName : $customerName,
                    'origin_address' => $isReturnDelivery ? $shopAddr : $customerAddr,
                    'origin_phone' => $isReturnDelivery ? $shopPhone : $customerPhone,
                    'destination_title' => $isReturnDelivery ? '📍 2. Drop-off Destination (Customer Home)' : '🏬 2. Drop-off Destination (Laundry Shop)',
                    'destination_name' => $isReturnDelivery ? $customerName : $shopName,
                    'destination_address' => $isReturnDelivery ? ($order->delivery_address ?? $customerAddr) : $shopAddr,
                    'destination_phone' => $isReturnDelivery ? $customerPhone : $shopPhone,
                    'delivery_fee' => number_format((float) $order->delivery_fee, 2),
                    'total_amount' => number_format((float) $order->total_amount, 2),
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Rider/Orders/Index', [
            'profile' => $profile,
            'activeSubscription' => $activeSub,
            'availableOrders' => $availableOrders,
            'myActiveDeliveries' => $myActiveDeliveries,
        ]);
    }

    public function toggleOnline(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        if (!$profile) {
            return back()->with('error', 'Rider profile not found.');
        }

        // Check active rider subscription pass
        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'rider')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '>', now())
            ->first();

        if (!$activeSub && !$profile->is_online) {
            return redirect()->route('rider.subscription')->with('error', '⚡ Your Monthly Rider Pass is expired or inactive. Renew your pass via Paystack to go online for delivery dispatches!');
        }

        $isOnline = $this->riderService->toggleOnline($profile);

        return back()->with('success', $isOnline ? 'You are now ONLINE for dispatch requests.' : 'You are now OFFLINE.');
    }

    public function submitBid(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        if (!$profile || !$profile->is_online) {
            return back()->with('error', 'You must be ONLINE to submit delivery fee proposals.');
        }

        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'rider')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '>', now())
            ->first();

        if (!$activeSub) {
            return redirect()->route('rider.subscription')->with('error', '⚡ Active Monthly Rider Pass required to negotiate dispatches!');
        }

        if ($order->rider_id) {
            return back()->with('error', 'This order has already been assigned to a rider.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'note' => 'nullable|string|max:255',
        ]);

        $bid = DispatchBid::updateOrCreate(
            [
                'order_id' => $order->id,
                'rider_id' => $user->id,
            ],
            [
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
            ]
        );

        try {
            event(new BidSubmitted($bid));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("BidSubmitted broadcast notice: " . $e->getMessage());
        }

        return back()->with('success', "💰 Proposed delivery fee of ₦" . number_format((float) $validated['amount']) . " submitted for customer negotiation!");
    }

    public function acceptOrder(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->riderProfile;

        if (!$profile || !$profile->is_online) {
            return back()->with('error', 'You must be ONLINE to accept dispatch requests.');
        }

        // Check active subscription
        $activeSub = Subscription::where('user_id', $user->id)
            ->where('role', 'rider')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '>', now())
            ->first();

        if (!$activeSub) {
            return redirect()->route('rider.subscription')->with('error', '⚡ Active Monthly Rider Pass required to accept dispatches!');
        }

        if ($order->rider_id) {
            return back()->with('error', 'This order dispatch was already accepted by another rider.');
        }

        $newStatus = ($order->status === OrderStatus::READY_FOR_DELIVERY || $order->status === OrderStatus::READY_FOR_DELIVERY->value) 
            ? OrderStatus::OUT_FOR_DELIVERY 
            : OrderStatus::PICKUP_ASSIGNED;

        $targetStatusStr = is_object($newStatus) ? $newStatus->value : $newStatus;
        $order->logStatusChange($targetStatusStr, $user, "Rider {$user->first_name} accepted dispatch");

        $order->update([
            'rider_id' => $user->id,
            'status' => $newStatus,
        ]);

        // Mark rider's bid accepted if any
        DispatchBid::where('order_id', $order->id)->where('rider_id', $user->id)->update(['status' => 'accepted']);
        DispatchBid::where('order_id', $order->id)->where('rider_id', '!=', $user->id)->update(['status' => 'rejected']);

        // Broadcast real-time order acceptance to shop & system
        try {
            event(new OrderAcceptedByRider($order, $user));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("OrderAcceptedByRider broadcast notice: " . $e->getMessage());
        }

        return back()->with('success', "🎉 Dispatch #{$order->order_number} successfully accepted!");
    }

    public function declineOrder(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        DispatchBid::updateOrCreate(
            [
                'order_id' => $order->id,
                'rider_id' => $user->id,
            ],
            [
                'amount' => (float) $order->delivery_fee,
                'note' => 'Declined by rider',
                'status' => 'rejected_by_rider',
            ]
        );

        return back()->with('success', "Dispatch request #{$order->order_number} declined.");
    }

    public function updateDeliveryStatus(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($order->rider_id !== $user->id) {
            return back()->with('error', 'You are not assigned to this delivery.');
        }

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === 'completed' || $newStatus === OrderStatus::COMPLETED->value) {
            return back()->with('error', 'Only customers can confirm receipt and mark an order as completed.');
        }

        if ($newStatus === 'dropped_off_at_shop') {
            // Phase 1 pickup complete: Garments delivered to laundry shop!
            // Hand over order to shop owner for cleaning, reset rider assignment & dispatch flag.
            $order->logStatusChange(OrderStatus::GARMENTS_PICKED_UP->value, $user, "Rider {$user->first_name} delivered garments to shop. Handed over for cleaning.");
            $order->update([
                'status' => OrderStatus::GARMENTS_PICKED_UP,
                'rider_id' => null,
                'is_dispatch_requested' => false,
            ]);

            \App\Services\NotificationService::send(
                $order->customer_id,
                "Garments Delivered to Shop",
                "Rider {$user->first_name} has delivered your garments to {$order->shop?->name} for cleaning!",
                "/orders/{$order->id}",
                "order_status"
            );

            return back()->with('success', "🧺 Garments for Order #{$order->order_number} successfully delivered to the laundry shop! Shop owner notified to begin cleaning.");
        }

        $order->logStatusChange($newStatus, $user, "Rider {$user->first_name} updated status to " . str_replace('_', ' ', $newStatus));
        $order->update([
            'status' => $newStatus,
        ]);

        if ($newStatus === 'out_for_delivery' || $newStatus === OrderStatus::OUT_FOR_DELIVERY->value) {
            \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'out_for_delivery');
        }

        return back()->with('success', "Delivery #{$order->order_number} status updated to " . str_replace('_', ' ', $newStatus));
    }
}
