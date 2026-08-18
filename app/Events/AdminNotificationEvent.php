<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $notificationData;

    public function __construct(
        public string $title,
        public string $message,
        public string $type, // 'user_registered', 'kyc_submitted', 'rider_registered'
        public ?string $url = null,
        public ?array $meta = []
    ) {
        $this->notificationData = [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'url' => $this->url ?? '/admin/dashboard',
            'meta' => $this->meta ?? [],
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.notification';
    }
}
