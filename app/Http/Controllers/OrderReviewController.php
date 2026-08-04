<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderReviewController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();

        if ($order->customer_id && $order->customer_id !== $user->id) {
            return back()->with('error', 'Unauthorized to review this order.');
        }

        if ($order->review) {
            return back()->with('error', 'You have already submitted a review for this order.');
        }

        $validated = $request->validate([
            'shop_rating' => 'required|integer|min:1|max:5',
            'rider_rating' => 'nullable|integer|min:1|max:5',
            'shop_comment' => 'nullable|string|max:1000',
            'rider_comment' => 'nullable|string|max:1000',
        ]);

        OrderReview::create([
            'order_id' => $order->id,
            'customer_id' => $user->id,
            'shop_id' => $order->shop_id,
            'rider_id' => $order->rider_id,
            'shop_rating' => $validated['shop_rating'],
            'rider_rating' => $validated['rider_rating'] ?? 5,
            'shop_comment' => $validated['shop_comment'] ?? null,
            'rider_comment' => $validated['rider_comment'] ?? null,
        ]);

        $order->logStatusChange($order->status->value ?? (string)$order->status, $user, "Customer submitted a {$validated['shop_rating']}⭐ review for shop");

        return back()->with('success', '⭐ Thank you for rating your laundry & delivery experience!');
    }
}
