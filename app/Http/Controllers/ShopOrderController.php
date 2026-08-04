<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PickupRequested;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Service;
use App\Models\User;
use App\Services\ShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        $orders = Order::where('shop_id', $shop->id)
            ->with(['customer', 'rider', 'items.category', 'items.service'])
            ->latest()
            ->paginate(15);

        $categories = Category::withoutGlobalScopes()->where('shop_id', $shop->id)->where('is_active', true)->get();
        $services = Service::withoutGlobalScopes()->where('shop_id', $shop->id)->where('is_active', true)->get();
        $prices = Price::withoutGlobalScopes()->where('shop_id', $shop->id)->with(['category', 'service'])->get();

        return Inertia::render('Shop/Orders/Index', [
            'shop' => $shop,
            'orders' => $orders,
            'categories' => $categories,
            'services' => $services,
            'prices' => $prices,
        ]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        if (!$shop->hasActiveSubscription()) {
            return back()->with('error', '⚡ Active Subscription Required! Your shop does not have an active monthly plan. Please subscribe via Subscription & Billing to log orders.');
        }

        $validated = $request->validate([
            'legacy_customer_name' => 'required|string|max:255',
            'legacy_customer_phone' => 'required|string|max:20',
            'fulfillment_type' => 'required|string|in:home_delivery,store_pickup',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

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

        // Check if customer phone already registered
        $existingCustomer = User::where('phone', $validated['legacy_customer_phone'])->first();

        $order = Order::create([
            'order_number' => $orderNumber,
            'shop_id' => $shop->id,
            'customer_id' => $existingCustomer?->id,
            'fulfillment_type' => $validated['fulfillment_type'],
            'status' => OrderStatus::CLEANING_IN_PROGRESS,
            'payment_status' => PaymentStatus::PENDING,
            'payment_method' => 'cash_on_delivery',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'pickup_address' => $shop->address,
            'delivery_address' => $shop->address,
            'is_legacy' => true,
            'legacy_customer_name' => $validated['legacy_customer_name'],
            'legacy_customer_phone' => $validated['legacy_customer_phone'],
            'notes' => $validated['notes'] ?? 'Manual walk-in order logged by shop owner',
        ]);

        $order->logStatusChange(OrderStatus::CLEANING_IN_PROGRESS->value, $request->user(), 'Manual walk-in order logged by shop owner');

        foreach ($orderItemsData as $item) {
            $order->items()->create($item);
        }

        return back()->with('success', "Manual legacy order #{$order->order_number} created successfully!");
    }

    public function linkCustomer(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'query' => 'required|string',
        ]);

        $query = trim($validated['query']);

        $user = User::where('phone', $query)
            ->orWhere('email', $query)
            ->orWhere('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->first();

        if (!$user) {
            return back()->with('error', "No registered user found matching '{$query}'. Ask customer to register with phone number '{$order->legacy_customer_phone}'.");
        }

        $order->update([
            'customer_id' => $user->id,
        ]);

        return back()->with('success', "Order #{$order->order_number} successfully linked to customer '{$user->first_name} {$user->last_name}'!");
    }

    public function requestPickup(Request $request, Order $order): RedirectResponse
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        if ($order->shop_id !== $shop->id) {
            return back()->with('error', 'Unauthorized access to this order.');
        }

        if ($order->rider_id) {
            return back()->with('error', "A rider is already assigned to this order ({$order->rider?->first_name}).");
        }

        $order->logStatusChange($order->status->value ?? (string)$order->status, $request->user(), 'Shop owner broadcasted pickup dispatch request to riders');
        $order->update(['is_dispatch_requested' => true]);

        try {
            event(new PickupRequested($order));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("PickupRequested event skipped: " . $e->getMessage());
        }

        return back()->with('success', "🚀 Pickup dispatch request #{$order->order_number} broadcasted to all active riders!");
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $updateData = ['status' => $validated['status']];

        if (in_array($validated['status'], [OrderStatus::READY_FOR_DELIVERY->value, OrderStatus::READY_FOR_PICKUP->value])) {
            $updateData['is_dispatch_requested'] = true;
            $updateData['rider_id'] = null; // Reset rider assignment so return delivery can be accepted/bid by ANY active rider!
        }

        $order->logStatusChange($validated['status'], $request->user(), "Status updated by shop owner to " . str_replace('_', ' ', $validated['status']));
        $order->update($updateData);

        if ($validated['status'] === OrderStatus::CLEANING_IN_PROGRESS->value || $validated['status'] === 'cleaning_in_progress') {
            \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'garments_in_shop');
        } elseif (in_array($validated['status'], [OrderStatus::READY_FOR_DELIVERY->value, OrderStatus::READY_FOR_PICKUP->value])) {
            \App\Services\SmsNotificationService::sendOrderStatusAlert($order, 'ready_for_delivery');
            try {
                event(new PickupRequested($order));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("PickupRequested event skipped: " . $e->getMessage());
            }
        }

        return back()->with('success', "Order #{$order->order_number} status updated to " . str_replace('_', ' ', $validated['status']));
    }

    public function printTag(Request $request, Order $order): \Inertia\Response
    {
        $shop = app(ShopContext::class)->get() ?? $request->user()?->ownedShops()?->firstOrFail();

        if ($order->shop_id !== $shop->id) {
            abort(403, 'Unauthorized access to this order tag.');
        }

        $order->load(['shop', 'customer', 'items.category', 'items.service']);

        return \Inertia\Inertia::render('Shop/Orders/PrintTag', [
            'order' => $order,
        ]);
    }
}
