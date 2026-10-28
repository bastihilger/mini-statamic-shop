<?php

namespace Bastihilger\MiniStatamicShop\Http\Middleware;

use Bastihilger\MiniStatamicShop\Cart;
use Bastihilger\MiniStatamicShop\Checkout;
use Bastihilger\MiniStatamicShop\Orders;
use Closure;
use Illuminate\Http\Request;

/**
 * When Stripe redirects back to the thank-you page, check the order right
 * away and empty the cart if this browser's checkout session was paid.
 */
class HandleThankYouPage
{
    public function handle(Request $request, Closure $next)
    {
        $sessionId = $request->query('session_id');

        if ($request->isMethod('GET')
            && is_string($sessionId)
            && trim($request->path(), '/') === trim(config('mini-shop.urls.thankyou'), '/')
            && $sessionId === session(Checkout::SESSION_KEY)
        ) {
            $orders = app(Orders::class);

            if (($order = $orders->findBySession($sessionId)) && $orders->check($order)) {
                app(Cart::class)->clear();
                session()->forget(Checkout::SESSION_KEY);
            }
        }

        return $next($request);
    }
}
