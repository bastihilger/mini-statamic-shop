<?php

namespace Bastihilger\MiniStatamicShop\Tags;

use Bastihilger\MiniStatamicShop\Cart;
use Bastihilger\MiniStatamicShop\Checkout;
use Bastihilger\MiniStatamicShop\Products;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Js;
use Statamic\Tags\Tags;

class MiniShop extends Tags
{
    protected static $handle = 'mini_shop';

    public const CHECKOUT_FIELDS = [
        ['name' => 'salutation', 'required' => true],
        ['name' => 'first_name', 'required' => true, 'autocomplete' => 'given-name'],
        ['name' => 'last_name', 'required' => true, 'autocomplete' => 'family-name'],
        ['name' => 'street', 'required' => true, 'autocomplete' => 'address-line1'],
        ['name' => 'street2', 'required' => false, 'autocomplete' => 'address-line2'],
        ['name' => 'postcode', 'required' => true, 'autocomplete' => 'postal-code'],
        ['name' => 'city', 'required' => true, 'autocomplete' => 'address-level2'],
        ['name' => 'country', 'required' => true],
        ['name' => 'phone', 'required' => false, 'autocomplete' => 'tel'],
    ];

    public function __construct(protected Products $products, protected Cart $cart, protected Checkout $checkout)
    {
    }

    /**
     * {{ mini_shop:products }} ... {{ /mini_shop:products }}
     * Published products, sorted; filtered by ?category= if a categories taxonomy is configured.
     */
    public function products()
    {
        $category = request()->query('category');

        return $this->products->sorted(is_string($category) ? $category : null);
    }

    /**
     * Alias kept for templates migrated from KraenkShop.
     */
    public function sortedProducts()
    {
        return $this->products();
    }

    public function productCount()
    {
        return $this->products->count();
    }

    public function cartCount()
    {
        return $this->cart->count();
    }

    /**
     * {{ mini_shop:buy :id="id" }}
     */
    public function buy()
    {
        $product = $this->products->find($this->params->get('id', $this->context->value('id')));

        if (! $product) {
            return '';
        }

        return view('mini-shop::buy', ['product' => $this->products->toArray($product)])->render();
    }

    public function tinycart()
    {
        return view('mini-shop::tinycart')->render();
    }

    public function cartLink()
    {
        return view('mini-shop::cart-link', ['count' => $this->cart->count()])->render();
    }

    /**
     * {{ mini_shop:cart }} – optional parameters override the texts and URLs:
     * empty_cart_text, total_text, remove_item_text, checkout_url, checkout_text,
     * continue_shopping_url, continue_shopping_text.
     */
    public function cart()
    {
        $params = collect([
            'emptyCartText' => 'empty_cart_text',
            'totalText' => 'total_text',
            'removeItemText' => 'remove_item_text',
            'checkoutUrl' => 'checkout_url',
            'checkoutText' => 'checkout_text',
            'continueShoppingUrl' => 'continue_shopping_url',
            'continueShoppingText' => 'continue_shopping_text',
        ])->map(fn ($param, $camel) => $this->params->get([$param, $camel]))->filter()->all();

        return view('mini-shop::cart', $params)->render();
    }

    public function checkout()
    {
        if ($this->cart->isEmpty()) {
            throw new HttpResponseException(redirect(config('mini-shop.urls.cart')));
        }

        return view('mini-shop::checkout', [
            'cart' => $this->cart->toArray(),
            'address' => $this->checkout->address(),
            'fields' => self::CHECKOUT_FIELDS,
        ])->render();
    }

    /**
     * {{ mini_shop:scripts }} – put it in the <head>. Pass bundled="true" if you
     * import resources/dist/mini-shop.js in your own JS bundle.
     */
    public function scripts()
    {
        $config = [
            'csrf' => csrf_token(),
            'cart' => $this->cart->toArray(),
            'endpoints' => [
                'add' => route('statamic.mini-shop.cart.add', [], false),
                'update' => route('statamic.mini-shop.cart.update', [], false),
                'checkout' => route('statamic.mini-shop.checkout', [], false),
            ],
            'urls' => config('mini-shop.urls'),
            'texts' => trans('mini-shop::shop'),
        ];

        $html = '<script>window.miniShop = '.Js::from($config).';</script>';

        if (! $this->params->bool('bundled')) {
            $path = 'vendor/mini-statamic-shop/mini-shop.js';
            $version = @filemtime(public_path($path)) ?: '1';
            $html .= "\n".'<script src="/'.$path.'?v='.$version.'"></script>';
        }

        return $html;
    }
}
