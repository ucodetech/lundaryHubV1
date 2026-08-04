<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAcceptedByRider implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $orderData;

    public function __construct(public Order $order, public User $rider)
    {
        $this->orderData = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'rider_id' => $rider->id,
            'rider_name' => $rider->first_name . ' ' . $rider->last_name,
            'rider_phone' => $rider->phone ?? 'N/A',
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('shop.' . $this->order->shop_id),
            new Channel('available-dispatches'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.accepted';
    }
}
