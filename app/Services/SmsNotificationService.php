<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    /**
     * Send SMS notification for order status updates.
     */
    public static function sendOrderStatusAlert(Order $order, string $eventKey): void
    {
        $phone = $order->customer?->phone ?? $order->legacy_customer_phone;

        if (!$phone) {
            Log::info("SMS Notification skipped for Order #{$order->order_number}: No phone number registered.");
            return;
        }

        $customerName = $order->customer?->first_name ?? $order->legacy_customer_name ?? 'Customer';
        $shopName = $order->shop?->name ?? 'LaundryHub Shop';
        $orderNum = $order->order_number;

        $message = match ($eventKey) {
            'order_submitted' => "Hello {$customerName}, your laundry booking #{$orderNum} with {$shopName} has been received! Track status live on LaundryHub.",
            'pickup_assigned' => "Hi {$customerName}, rider " . ($order->rider?->first_name ?? 'assigned') . " is en route to pick up your garments for Order #{$orderNum}.",
            'garments_in_shop' => "Hi {$customerName}, your garments for Order #{$orderNum} have arrived safely at {$shopName} and washing is in progress! 🧼",
            'ready_for_delivery' => "Hi {$customerName}, your clean garments for Order #{$orderNum} are ready & being dispatched for return delivery!",
            'out_for_delivery' => "Hi {$customerName}, rider " . ($order->rider?->first_name ?? 'assigned') . " is out for delivery with your clean garments for Order #{$orderNum}! 🚚",
            'order_completed' => "Thank you {$customerName}! Order #{$orderNum} is delivered & complete. Please rate your experience on LaundryHub ⭐",
            default => "Hi {$customerName}, your LaundryHub Order #{$orderNum} status is now: " . str_replace('_', ' ', $order->status->value ?? (string)$order->status),
        };

        static::dispatchSms($phone, $message);
    }

    /**
     * Dispatch SMS message via Termii / Twilio HTTP API with local fallback logging.
     */
    protected static function dispatchSms(string $recipientPhone, string $message): void
    {
        $apiKey = config('services.termii.api_key') ?? env('TERMII_API_KEY');
        $senderId = config('services.termii.sender_id') ?? env('TERMII_SENDER_ID', 'LaundryHub');

        Log::info("📱 [SMS DISPATCH] To: {$recipientPhone} | Message: \"{$message}\"");

        if ($apiKey) {
            try {
                Http::post('https://api.ng.termii.com/api/sms/send', [
                    'to' => $recipientPhone,
                    'from' => $senderId,
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => 'generic',
                    'api_key' => $apiKey,
                ]);
            } catch (\Throwable $e) {
                Log::error("SMS Gateway API error: " . $e->getMessage());
            }
        }
    }
}
