<?php

namespace Bastihilger\MiniStatamicShop;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;

class Products
{
    public function field(string $key): string
    {
        return config("mini-shop.fields.{$key}", $key);
    }

    /**
     * Published products, sorted by the sort field and title, optionally
     * filtered by the configured categories taxonomy.
     */
    public function sorted(?string $categorySlug = null): Collection
    {
        $products = Entry::query()
            ->where('collection', config('mini-shop.collections.products'))
            ->whereStatus('published')
            ->get();

        if ($taxonomy = config('mini-shop.categories')) {
            $category = $categorySlug
                ? Term::find("{$taxonomy}::{$categorySlug}")
                : Term::query()->where('taxonomy', $taxonomy)->get()->first();

            $field = $this->field('category');

            $products = $products->filter(
                fn ($product) => $category && in_array($category->slug(), Arr::wrap($product->get($field)))
            );
        }

        return $products
            ->sortBy(fn ($product) => $product->get($this->field('sort')).'-'.$product->get('title'))
            ->values();
    }

    public function count(): int
    {
        return Entry::query()
            ->where('collection', config('mini-shop.collections.products'))
            ->whereStatus('published')
            ->count();
    }

    public function find(?string $id)
    {
        $entry = $id ? Entry::find($id) : null;

        if (! $entry || $entry->collectionHandle() !== config('mini-shop.collections.products') || ! $entry->published()) {
            return null;
        }

        return $entry;
    }

    /**
     * The product as the front end needs it. Prices come from the entry, never from the browser.
     */
    public function toArray($entry): array
    {
        $image = $entry->augmentedValue($this->field('image'))->value();
        if ($image instanceof Collection || is_array($image)) {
            $image = collect($image)->first();
        }

        return [
            'id' => $entry->id(),
            'title' => $entry->get('title'),
            'url' => $entry->url(),
            'image' => $image ? $image->url() : '',
            'articles' => $this->articles($entry)->values()->all(),
        ];
    }

    public function articles($entry): Collection
    {
        return collect($entry->get($this->field('articles')) ?? [])
            ->filter(fn ($article) => is_array($article)
                && ($article['enabled'] ?? true)
                && filled($article[$this->field('price')] ?? null)
                && filled($article[$this->field('article_number')] ?? null))
            ->map(function ($article) {
                $price = Money::parse($article[$this->field('price')]);

                return [
                    'article_number' => (string) $article[$this->field('article_number')],
                    'price' => $price,
                    'readable_price' => Money::decimal($price),
                    'attributes' => collect($article[$this->field('attributes')] ?? [])
                        ->filter(fn ($attribute) => is_array($attribute) && filled($attribute['title'] ?? null))
                        ->map(fn ($attribute) => [
                            'title' => $attribute['title'],
                            'value' => $attribute['value'] ?? '',
                        ])
                        ->values()
                        ->all(),
                ];
            });
    }

    public function article($entry, string $articleNumber): ?array
    {
        return $this->articles($entry)->firstWhere('article_number', $articleNumber);
    }
}
