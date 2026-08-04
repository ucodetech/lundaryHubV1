<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PickupRequested implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $orderData;

    public function __construct(public Order $order)
    {
        $order->load(['shop', 'customer', 'items.category', 'items.service']);

        $this->orderData = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'fulfillment_type' => $order->fulfillment_type->value,
            'shop_name' => $order->shop->name ?? 'Laundry Shop',
            'shop_address' => $order->shop->address ?? 'N/A',
            'shop_phone' => $order->shop->phone ?? 'N/A',
            'customer_name' => $order->is_legacy ? $order->legacy_customer_name : ($order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : 'Walk-in Customer'),
            'customer_phone' => $order->is_legacy ? $order->legacy_customer_phone : ($order->customer->phone ?? 'N/A'),
            'pickup_address' => $order->pickup_address ?? $order->shop->address,
            'delivery_address' => $order->delivery_address ?? $order->shop->address,
            'delivery_fee' => number_format((float) $order->delivery_fee, 2),
            'total_amount' => number_format((float) $order->total_amount, 2),
            'items_count' => $order->items->count(),
            'created_at' => $order->created_at->diffForHumans(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('available-dispatches'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pickup.requested';
    }
}
