<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send in-app notification to a user.
     */
    public static function send(User|int $user, string $title, string $message, ?string $link = null, string $type = 'system'): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);

        $userModel = $user instanceof User ? $user : User::find($userId);
        if ($userModel) {
            try {
                $userModel->notify(new \App\Notifications\WebPushAlert($title, $message, $link));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("WebPush Notice: " . $e->getMessage());
            }
        }

        return $notification;
    }
}
