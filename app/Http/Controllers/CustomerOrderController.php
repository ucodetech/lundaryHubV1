<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderAcceptedByRider;
use App\Models\Category;
use App\Models\DispatchBid;
use App\Models\Order;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        $orders = Order::where(function ($query) use ($user) {
            $query->where('customer_id', $user->id);
            if ($user->phone) {
                $query->orWhere('legacy_customer_phone', $user->phone);
            }
        })
        ->with(['shop', 'items.category', 'items.service'])
        ->latest()
        ->paginate(10);

        // If the user is a Shop Owner and has 0 personal orders as a customer, redirect to their Shop Orders dashboard
        if ($user->role === \App\Enums\UserRole::SHOP_OWNER->value && $orders->total() === 0) {
            return redirect()->route('shop.orders.index');
        }

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'fulfillment_type' => 'required|string|in:home_delivery,store_pickup',
            'payment_method' => 'required|string|in:cash_on_delivery,paystack',
            'pickup_address' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $shop = Shop::findOrFail($validated['shop_id']);

        // Check active subscription
        $activeSub = $shop->activeSubscription();
        if (!$activeSub) {
            return back()->with('error', 'This dry cleaner storefront is currently inactive for online bookings because it does not have an active subscription.');
        }

        // Check monthly order limits for starter plan
        if ($activeSub && $activeSub->plan?->order_limit) {
            $monthlyOrdersCount = Order::where('shop_id', $shop->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();

            if ($monthlyOrdersCount >= $activeSub->plan->order_limit) {
                return back()->with('error', "This shop has reached its monthly limit of {$activeSub->plan->order_limit} orders. Please try again next month or contact the shop.");
            }
        }

        if ($validated['fulfillment_type'] === 'home_delivery' && !$shop->offers_home_delivery) {
            return back()->with('error', 'This dry cleaner is currently only accepting In-Store Pickup orders.');
        }

        if ($validated['fulfillment_type'] === 'store_pickup' && !$shop->offers_store_pickup) {
            return back()->with('error', 'This dry cleaner is currently only accepting Home Delivery orders.');
        }

        $subtotal = 0;
        $orderItemsData = [];

        foreach ($validated['items'] as $itemData) {
            $cat = Category::find($itemData['category_id']);
            $svc = Service::find($itemData['service_id']);
            $unitPrice = (float) $itemData['unit_price'];
            $qty = (int) $itemData['quantity'];
            $itemSubtotal = $unitPrice * $qty;
            $subtotal += $itemSubtotal;

            $orderItemsData[] = [
                'category_id' => $cat->id,
                'service_id' => $svc->id,
                'category_name' => $cat->name,
                'service_name' => $svc->name,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'subtotal' => $itemSubtotal,
            ];
        }

        $deliveryFee = ($validated['fulfillment_type'] === 'home_delivery') ? (float) $shop->delivery_fee : 0.00;
        $totalAmount = $subtotal + $deliveryFee;
        $orderNumber = 'LHD-' . strtoupper(Str::random(8));

        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $request->user()->id,
            'shop_id' => $shop->id,
            'fulfillment_type' => $validated['fulfillment_type'],
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => $validated['payment_method'],
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'pickup_address' => $validated['pickup_address'] ?? $request->user()->address ?? $shop->address,
            'delivery_address' => $validated['delivery_address'] ?? $request->user()->address ?? $shop->address,
            'is_legacy' => false,
            'notes' => $validated['notes'],
        ]);

        foreach ($orderItemsData as $item) {
            $order->items()->create($item);
        }

        if ($validated['fulfillment_type'] === 'home_delivery') {
            try {
                event(new \App\Events\PickupRequested($order));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("PickupRequested broadcast notice: " . $e->getMessage());
            }
        }

        \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'order_submitted');

        if ($validated['payment_method'] === 'paystack') {
            return redirect()->route('paystack.initialize', $order->id);
        }

        return redirect()->route('orders.show', $order->order_number)->with('success', "Order #{$order->order_number} submitted successfully!");
    }

    public function show(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)
            ->with([
                'shop',
                'customer',
                'rider.riderProfile.kycDocuments',
                'items.category',
                'items.service',
                'bids.rider.riderProfile.kycDocuments',
                'statusLogs.user',
                'review'
            ])
            ->firstOrFail();

        return Inertia::render('Customer/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function acceptBid(Request $request, Order $order, DispatchBid $bid): RedirectResponse
    {
        $user = $request->user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Unauthorized access to this order.');
        }

        if ($bid->order_id !== $order->id) {
            return back()->with('error', 'Invalid bid for this order.');
        }

        if ($order->rider_id) {
            return back()->with('error', 'A rider has already been assigned to this order.');
        }

        // Accept chosen bid, reject all others
        $bid->update(['status' => 'accepted']);
        DispatchBid::where('order_id', $order->id)->where('id', '!=', $bid->id)->update(['status' => 'rejected']);

        $newFee = (float) $bid->amount;
        $newTotal = (float) $order->subtotal + $newFee;

        $newStatus = ($order->status === OrderStatus::READY_FOR_DELIVERY || $order->status === OrderStatus::READY_FOR_DELIVERY->value) 
            ? OrderStatus::OUT_FOR_DELIVERY 
            : OrderStatus::PICKUP_ASSIGNED;

        $order->logStatusChange(is_object($newStatus) ? $newStatus->value : $newStatus, $user, "Customer accepted delivery bid of ₦" . number_format($newFee, 2) . " from Rider {$bid->rider?->first_name}");

        $order->update([
            'rider_id' => $bid->rider_id,
            'delivery_fee' => $newFee,
            'total_amount' => $newTotal,
            'status' => $newStatus,
        ]);

        \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'pickup_assigned');

        try {
            event(new OrderAcceptedByRider($order, $bid->rider));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("OrderAcceptedByRider broadcast notice: " . $e->getMessage());
        }

        return back()->with('success', "🎉 Delivery fee of ₦" . number_format($newFee, 2) . " agreed! Rider {$bid->rider?->first_name} assigned.");
    }

    public function rejectBid(Request $request, Order $order, DispatchBid $bid): RedirectResponse
    {
        $user = $request->user();

        if ($order->customer_id !== $user->id) {
            return back()->with('error', 'Unauthorized access to this order.');
        }

        $bid->update(['status' => 'rejected']);

        return back()->with('success', 'Rider delivery fee offer declined.');
    }

    public function confirmDelivery(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($order->customer_id !== $user->id && $order->customer_id !== null) {
            return back()->with('error', 'Unauthorized action for this order.');
        }

        $order->logStatusChange(OrderStatus::COMPLETED->value, $user, 'Customer confirmed receipt of clean garments');

        $order->update([
            'status' => OrderStatus::COMPLETED,
        ]);

        \App\Services\ReferralService::rewardFirstOrderCompletion($order);
        \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'order_completed');

        return back()->with('success', '🎉 Delivery receipt confirmed! Thank you for using LaundryHub.');
    }
}
