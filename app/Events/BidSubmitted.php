<?php

namespace App\Events;

use App\Models\DispatchBid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $bidData;

    public function __construct(public DispatchBid $bid)
    {
        $bid->load(['rider.riderProfile', 'order']);

        $this->bidData = [
            'id' => $bid->id,
            'order_id' => $bid->order_id,
            'order_number' => $bid->order->order_number,
            'rider_id' => $bid->rider_id,
            'rider_name' => $bid->rider->first_name . ' ' . $bid->rider->last_name,
            'rider_phone' => $bid->rider->phone ?? 'N/A',
            'vehicle_type' => $bid->rider->riderProfile?->vehicle_type ?? 'Bicycle',
            'amount' => number_format((float) $bid->amount, 2),
            'note' => $bid->note,
            'status' => $bid->status,
            'created_at' => $bid->created_at->diffForHumans(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('order.' . $this->bid->order_id),
            new Channel('shop.' . $this->bid->order->shop_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.submitted';
    }
}
