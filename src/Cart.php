<?php

namespace Bastihilger\MiniStatamicShop;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;

/**
 * The cart lives in the session as a list of product id, article number
 * and quantity. Everything else (titles, prices, shipping, deposit) is
 * looked up from the entries whenever the cart is read.
 */
class Cart
{
    public const SESSION_KEY = 'mini-shop.cart';

    public const MAX_QUANTITY = 99;

    public function __construct(protected Products $products)
    {
    }

    public function lines(): Collection
    {
        return collect(session(self::SESSION_KEY, []));
    }

    public function add(string $productId, string $articleNumber, int $quantity = 1): void
    {
        $product = $this->products->find($productId);

        if (! $product || ! $this->products->article($product, $articleNumber)) {
            return;
        }

        $lines = $this->lines();
        $index = $lines->search(fn ($line) => $line['product_id'] === $productId && $line['article_number'] === $articleNumber);

        if ($index === false) {
            $lines->push(['product_id' => $productId, 'article_number' => $articleNumber, 'quantity' => 0]);
            $index = $lines->count() - 1;
        }

        $lines = $lines->map(function ($line, $i) use ($index, $quantity) {
            if ($i === $index) {
                $line['quantity'] = $this->clamp($line['quantity'] + $quantity);
            }

            return $line;
        });

        $this->store($lines);
    }

    public function update(string $productId, string $articleNumber, int $quantity): void
    {
        $lines = $this->lines()
            ->map(function ($line) use ($productId, $articleNumber, $quantity) {
                if ($line['product_id'] === $productId && $line['article_number'] === $articleNumber) {
                    $line['quantity'] = $quantity > 0 ? $this->clamp($quantity) : 0;
                }

                return $line;
            })
            ->filter(fn ($line) => $line['quantity'] > 0);

        $this->store($lines);
    }

    public function remove(string $productId, string $articleNumber): void
    {
        $this->update($productId, $articleNumber, 0);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    /**
     * Cart lines resolved against the current product entries. Lines whose
     * product or article no longer exists are dropped.
     */
    public function items(): Collection
    {
        return $this->lines()
            ->map(function ($line) {
                $product = $this->products->find($line['product_id']);
                $article = $product ? $this->products->article($product, $line['article_number']) : null;

                if (! $article) {
                    return null;
                }

                return [
                    'product' => $this->products->toArray($product),
                    'article' => $article,
                    'quantity' => (int) $line['quantity'],
                    'total' => $article['price'] * (int) $line['quantity'],
                    'entry' => $product,
                ];
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->items()->sum('total');
    }

    /**
     * Shipping is calculated per shipping rate entry: the quantities of all
     * products that share a rate are added up, and the first step whose
     * "max_items" fits applies. A step without max_items (or 0, "-") has no limit.
     */
    public function shipping(): int
    {
        $field = $this->products->field('shipping_rate');

        return $this->items()
            ->groupBy(fn ($item) => Arr::first(Arr::wrap($item['entry']->get($field))) ?? '')
            ->reject(fn ($items, $rateId) => $rateId === '')
            ->map(function ($items, $rateId) {
                $rate = Entry::find($rateId);

                if (! $rate) {
                    return 0;
                }

                $quantity = $items->sum('quantity');

                $step = collect($rate->get('steps') ?? [])
                    ->map(fn ($step) => [
                        'max' => (int) ($step['max_items'] ?? 0) ?: PHP_INT_MAX,
                        'cost' => Money::parse($step['cost'] ?? 0),
                    ])
                    ->sortBy('max')
                    ->first(fn ($step) => $quantity <= $step['max']);

                return $step['cost'] ?? 0;
            })
            ->sum();
    }

    /**
     * Deposit entries store their cost per item in cents.
     */
    public function deposit(): int
    {
        $field = $this->products->field('deposit');

        return $this->items()->sum(function ($item) use ($field) {
            $deposit = ($id = Arr::first(Arr::wrap($item['entry']->get($field)))) ? Entry::find($id) : null;

            return $deposit ? $item['quantity'] * (int) $deposit->get('cost') : 0;
        });
    }

    public function total(): int
    {
        return $this->subtotal() + $this->shipping() + $this->deposit();
    }

    public function toArray(): array
    {
        $subtotal = $this->subtotal();
        $shipping = $this->shipping();
        $deposit = $this->deposit();

        return [
            'items' => $this->items()->map(fn ($item) => [
                'product' => $item['product'],
                'article' => $item['article'],
                'quantity' => $item['quantity'],
                'total' => $item['total'],
                'readable_total' => Money::format($item['total']),
                'readable_price' => Money::format($item['article']['price']),
            ])->all(),
            'count' => $this->count(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'deposit' => $deposit,
            'total' => $subtotal + $shipping + $deposit,
            'readable_subtotal' => Money::format($subtotal),
            'readable_shipping' => Money::format($shipping),
            'readable_deposit' => Money::format($deposit),
            'readable_total' => Money::format($subtotal + $shipping + $deposit),
        ];
    }

    protected function store(Collection $lines): void
    {
        session([self::SESSION_KEY => $lines->values()->all()]);
    }

    protected function clamp(int $quantity): int
    {
        return max(1, min(self::MAX_QUANTITY, $quantity));
    }
}
