<div class="stripeshop-tinycart" x-data>
    <template x-if="$store.miniShop.isEmpty">
        <div>{{ __('mini-shop::shop.cart_is_empty') }}</div>
    </template>

    <template x-if="!$store.miniShop.isEmpty">
        <div>
            <div class="stripeshop-tinycart-items">
                <template x-for="item in $store.miniShop.cart.items" :key="item.product.id + '-' + item.article.article_number">
                    <div class="stripeshop-tinycart-row">
                        <div class="stripeshop-tinycart-col is-left is-image" :style="{ backgroundImage: `url('${item.product.image}')` }"></div>

                        <div class="stripeshop-tinycart-col is-right">
                            <span x-text="item.quantity"></span> &times; <span x-text="item.product.title"></span>
                            <template x-if="item.article.attributes.length">
                                <span x-text="' / ' + $store.miniShop.attributes(item)"></span>
                            </template>
                            <br>
                            <span x-text="item.readable_total"></span>
                            <span class="stripeshop-tinycart-singular-price" x-show="item.quantity > 1">
                                (<span x-text="item.quantity"></span> &times; <span x-text="item.readable_price"></span>)
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="stripeshop-tinycart-row is-total">
                <div class="stripeshop-tinycart-col is-left">{{ __('mini-shop::shop.total') }}:</div>
                <div class="stripeshop-tinycart-col is-right" x-text="$store.miniShop.cart.readable_subtotal"></div>
            </div>

            <div class="stripeshop-tinycart-buttons">
                <a href="{{ config('mini-shop.urls.cart') }}" class="stripeshop-button is-secondary">{{ __('mini-shop::shop.edit') }}</a>
                <a href="{{ config('mini-shop.urls.checkout') }}" class="stripeshop-button">{{ __('mini-shop::shop.checkout') }}</a>
            </div>
        </div>
    </template>
</div>
