<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserApprovedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $approvalData;

    public function __construct(
        public int $userId,
        public string $role, // 'shop_owner', 'rider'
        public string $title,
        public string $message
    ) {
        $this->approvalData = [
            'userId' => $this->userId,
            'role' => $this->role,
            'title' => $this->title,
            'message' => $this->message,
            'approved_at' => now()->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.approved';
    }
}
