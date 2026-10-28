<a href="{{ config('mini-shop.urls.cart') }}" class="stripeshop-cart-link" x-data>
    (<span x-text="$store.miniShop.cart.count">{{ $count }}</span>) {{ __('mini-shop::shop.cart') }}
</a>
