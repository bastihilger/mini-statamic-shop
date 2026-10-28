<?php

namespace Bastihilger\MiniStatamicShop;

use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Entry;
use Stripe\StripeClient;

class Checkout
{
    public const SESSION_KEY = 'mini-shop.checkout_session';

    public const ADDRESS_KEY = 'mini-shop.address';

    public function __construct(protected Cart $cart, protected Orders $orders)
    {
    }

    public static function stripe(): StripeClient
    {
        return new StripeClient(config('mini-shop.stripe.secret'));
    }

    public function address(): array
    {
        return session(self::ADDRESS_KEY, [
            'email' => '',
            'shipping_same_as_invoice' => true,
            'invoice' => ['country' => array_key_first(config('mini-shop.countries')), 'salutation' => config('mini-shop.salutations.0')],
            'shipping' => ['country' => array_key_first(config('mini-shop.countries')), 'salutation' => config('mini-shop.salutations.0')],
        ]);
    }

    public function rememberAddress(array $address): void
    {
        session([self::ADDRESS_KEY => $address]);
    }

    /**
     * Creates an unpublished order entry and a Stripe Checkout Session for
     * the current cart. Returns the URL to redirect the customer to.
     */
    public function start(array $address): ?string
    {
        if ($this->cart->isEmpty() || $this->cart->total() <= 0) {
            return null;
        }

        $this->rememberAddress($address);

        $order = $this->createOrder($address);

        $params = [
            'mode' => 'payment',
            'line_items' => $this->lineItems(),
            'success_url' => url(config('mini-shop.urls.thankyou')).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url(config('mini-shop.urls.cancel')),
            'client_reference_id' => $order->id(),
            'metadata' => ['order_number' => $order->get('order_number')],
            'locale' => 'auto',
        ];

        if (filter_var($address['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            $params['customer_email'] = $address['email'];
        }

        try {
            $session = static::stripe()->checkout->sessions->create($params);
        } catch (\Throwable $e) {
            $order->delete();

            throw $e;
        }

        $order->set('stripe_session_id', $session->id)->save();

        session([self::SESSION_KEY => $session->id]);

        return $session->url;
    }

    protected function lineItems(): array
    {
        $currency = config('mini-shop.currency');

        $items = $this->cart->items()->map(fn ($item) => [
            'quantity' => $item['quantity'],
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $item['article']['price'],
                'product_data' => [
                    'name' => $this->itemName($item),
                    'metadata' => ['article_number' => $item['article']['article_number']],
                ],
            ],
        ])->all();

        foreach (['shipping_costs' => $this->cart->shipping(), 'deposit' => $this->cart->deposit()] as $label => $amount) {
            if ($amount > 0) {
                $items[] = [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $amount,
                        'product_data' => ['name' => __("mini-shop::shop.{$label}")],
                    ],
                ];
            }
        }

        return $items;
    }

    protected function itemName(array $item): string
    {
        $name = $item['product']['title'];

        foreach ($item['article']['attributes'] as $attribute) {
            $name .= ' / '.$attribute['title'].': '.$attribute['value'];
        }

        return $name.' (ArtNr '.$item['article']['article_number'].')';
    }

    protected function createOrder(array $address)
    {
        return Cache::lock('mini-shop.order-number', 10)->block(5, function () use ($address) {
            $number = $this->orders->nextNumber();

            $order = Entry::make()
                ->collection(config('mini-shop.collections.orders'))
                ->slug('order-'.$number)
                ->published(false)
                ->data([
                    'title' => 'Bestellung '.$number,
                    'order_number' => $number,
                    'shipcost' => Money::toFloat($this->cart->shipping()),
                    'deposit' => Money::toFloat($this->cart->deposit()),
                    'total' => Money::toFloat($this->cart->total()),
                    'articles' => $this->cart->items()->map(fn ($item) => [
                        'product_title' => $item['product']['title'],
                        'article_number' => $item['article']['article_number'],
                        'attributes' => collect($item['article']['attributes'])->map(fn ($a) => $a['title'].': '.$a['value'])->implode(', ') ?: null,
                        'quantity' => $item['quantity'],
                        'price' => Money::toFloat($item['article']['price']),
                    ])->all(),
                    // not created_at: the Eloquent driver uses that name for its own timestamp column
                    'ordered_at' => now()->format('Y-m-d H:i:s'),
                ] + $this->customerData($address));

            $order->save();

            return $order;
        });
    }

    protected function customerData(array $address): array
    {
        $invoice = $address['invoice'] ?? [];
        $shipping = ($address['shipping_same_as_invoice'] ?? true) ? $invoice : ($address['shipping'] ?? []);

        $data = ['invoice_email' => $address['email'] ?? null];

        foreach (['invoice' => $invoice, 'shipping' => $shipping] as $type => $values) {
            $data += [
                "{$type}_salutation" => $values['salutation'] ?? null,
                "{$type}_name" => trim(($values['first_name'] ?? '').' '.($values['last_name'] ?? '')),
                "{$type}_phone" => $values['phone'] ?? null,
                "{$type}_street" => $values['street'] ?? null,
                "{$type}_street2" => $values['street2'] ?? null,
                "{$type}_postal_code" => $values['postcode'] ?? null,
                "{$type}_city" => $values['city'] ?? null,
                "{$type}_country" => $values['country'] ?? null,
            ];
        }

        return $data;
    }
}
