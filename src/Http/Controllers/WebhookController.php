<?php

namespace Bastihilger\MiniStatamicShop\Http\Controllers;

use Bastihilger\MiniStatamicShop\Orders;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Stripe\Webhook;

/**
 * Optional: point a Stripe webhook for "checkout.session.completed" and
 * "checkout.session.async_payment_succeeded" at /!/mini-shop/webhook
 * and set STRIPE_WEBHOOK_SECRET.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, Orders $orders)
    {
        $secret = config('mini-shop.stripe.webhook_secret');

        abort_unless($secret, 404);

        try {
            $event = Webhook::constructEvent($request->getContent(), $request->header('Stripe-Signature'), $secret);
        } catch (\Throwable $e) {
            abort(400);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'])) {
            $session = $event->data->object;

            if ($session->payment_status === 'paid' && ($order = $orders->findBySession($session->id)) && ! $order->published()) {
                $orders->fulfill($order, $session->payment_intent);
            }
        }

        return response()->noContent();
    }
}
