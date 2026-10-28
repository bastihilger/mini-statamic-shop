<?php

namespace Bastihilger\MiniStatamicShop;

use Bastihilger\MiniStatamicShop\Mail\CustomerOrder;
use Bastihilger\MiniStatamicShop\Mail\ShopOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Statamic\Facades\Entry;

class Orders
{
    /**
     * Open orders older than this are no longer checked. Abandoned Checkout
     * Sessions expire after 24 hours, but delayed payment methods such as SEPA
     * direct debit can take up to 14 business days to succeed.
     */
    public const CHECK_DAYS = 21;

    public function query()
    {
        return Entry::query()->where('collection', config('mini-shop.collections.orders'));
    }

    public function nextNumber(): int
    {
        return (int) $this->query()->get()->max(fn ($order) => (int) $order->get('order_number')) + 1;
    }

    public function findBySession(string $sessionId)
    {
        return $this->query()->where('stripe_session_id', $sessionId)->first();
    }

    /**
     * Checks all recent unpaid orders against Stripe.
     */
    /**
     * Orders written by the original KraenkShop keep their date in "created_at".
     */
    public static function orderedAt($order): ?string
    {
        return $order->get('ordered_at') ?? $order->get('created_at');
    }

    public function checkOpen(): int
    {
        $since = now()->subDays(self::CHECK_DAYS)->format('Y-m-d H:i:s');

        return $this->query()
            ->whereStatus('draft')
            ->get()
            ->filter(fn ($order) => $order->get('stripe_session_id') && $order->get('stripe_session_status') !== 'expired' && static::orderedAt($order) >= $since)
            ->filter(fn ($order) => $this->check($order))
            ->count();
    }

    /**
     * Marks the order as paid if its Checkout Session is paid. Returns whether it is paid.
     */
    public function check($order): bool
    {
        if ($order->published()) {
            return true;
        }

        try {
            $session = Checkout::stripe()->checkout->sessions->retrieve($order->get('stripe_session_id'));
        } catch (\Throwable $e) {
            Log::warning('mini-shop: could not retrieve checkout session', ['order' => $order->id(), 'error' => $e->getMessage()]);

            return false;
        }

        if ($session->status === 'expired') {
            // abandoned checkout, no need to ask Stripe again
            $order->set('stripe_session_status', 'expired')->save();

            return false;
        }

        if ($session->payment_status !== 'paid') {
            return false;
        }

        $this->fulfill($order, $session->payment_intent);

        return true;
    }

    public function fulfill($order, ?string $paymentIntent = null): void
    {
        $order->published(true)
            ->set('stripe_payment_intent', $paymentIntent)
            ->set('paid_at', now()->format('Y-m-d H:i:s'))
            ->save();

        $this->sendShopMail($order);
        $this->sendCustomerMail($order);
    }

    public function sendShopMail($order, bool $force = false): void
    {
        if (! $force && $order->get('shop_mail_sent_at')) {
            return;
        }

        $recipients = collect(explode(',', (string) config('mini-shop.order_recipients')))->map(fn ($r) => trim($r))->filter();

        if ($recipients->isEmpty()) {
            Log::warning('mini-shop: no order_recipients configured, shop mail for order '.$order->get('order_number').' not sent.');

            return;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new ShopOrder($order));
        }

        $order->set('shop_mail_sent_at', now()->format('Y-m-d H:i:s'))->save();
    }

    public function sendCustomerMail($order, bool $force = false): void
    {
        if ((! $force && $order->get('customer_mail_sent_at')) || ! $order->get('invoice_email')) {
            return;
        }

        Mail::to($order->get('invoice_email'))->send(new CustomerOrder($order));

        $order->set('customer_mail_sent_at', now()->format('Y-m-d H:i:s'))->save();
    }

    /**
     * The order prepared for the mail templates.
     */
    public static function forMail($order): array
    {
        $data = $order->data()->all();

        $data['articles'] = collect($data['articles'] ?? [])->map(function ($article) {
            $price = Money::parse((float) ($article['price'] ?? 0));
            $total = $price * (int) ($article['quantity'] ?? 1);

            return $article + [
                'readable_price' => Money::format($price),
                'readable_total' => Money::format($total),
            ];
        })->all();

        foreach (['shipcost', 'deposit', 'total'] as $key) {
            $data["readable_{$key}"] = Money::format(Money::parse((float) ($data[$key] ?? 0)));
        }

        return $data;
    }
}
