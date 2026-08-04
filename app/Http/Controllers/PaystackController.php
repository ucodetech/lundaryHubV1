<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    public function __construct(protected PaystackService $paystackService)
    {
    }

    public function initialize(Request $request, Order $order)
    {
        $user = $request->user();
        $email = $user->email ?? 'customer@laundryhub.ng';
        $callbackUrl = route('paystack.callback');

        $result = $this->paystackService->initializeTransaction($order, $email, $callbackUrl);

        if ($result['success']) {
            if ($request->wantsJson()) {
                return response()->json($result);
            }
            return \Inertia\Inertia::location($result['authorization_url']);
        }

        if ($request->wantsJson()) {
            return response()->json($result, 400);
        }

        return back()->with('error', $result['message']);
    }

    public function callback(Request $request): RedirectResponse
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) {
            return redirect()->route('orders.index')->with('error', 'Invalid payment reference.');
        }

        $verification = $this->paystackService->verifyTransaction($reference);

        if ($verification['success']) {
            return redirect()->route('orders.show', $reference)->with('success', '🎉 Payment successful! Your order has been confirmed.');
        }

        return redirect()->route('orders.show', $reference)->with('error', 'Payment verification was unsuccessful or cancelled.');
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature') ?? '';
        $payload = $request->getContent();

        if (env('APP_ENV') === 'production') {
            if (!$this->paystackService->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Unauthorized Paystack webhook signature header');
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
            }
        }

        $event = json_decode($payload, true);

        if ($event && isset($event['event'])) {
            $eventType = $event['event'];
            $data = $event['data'] ?? [];

            Log::info("Paystack webhook received: {$eventType}");

            switch ($eventType) {
                case 'charge.success':
                    $this->paystackService->handleChargeSuccess($data);
                    break;

                case 'dedicated_account.assign.success':
                    $this->paystackService->handleDvaAssigned($data);
                    break;
            }
        }

        return response()->json(['status' => 'success']);
    }
}
