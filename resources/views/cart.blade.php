<div x-data="miniShopCart">
    <template x-if="$store.miniShop.isEmpty">
        <p>{{ $emptyCartText ?? __('mini-shop::shop.cart_is_empty') }}</p>
    </template>

    <template x-if="!$store.miniShop.isEmpty">
        <div>
            <div class="stripeshop-page-products">
                <template x-for="item in $store.miniShop.cart.items" :key="item.product.id + '-' + item.article.article_number">
                    <div class="stripeshop-page-product">
                        <div class="stripeshop-page-image" :style="{ backgroundImage: `url('${item.product.image}')` }"></div>

                        <div class="stripeshop-page-text">
                            <h5 class="stripeshop-page-title" x-text="item.product.title"></h5>
                            <div x-text="$store.miniShop.attributes(item)"></div>
                            <div class="stripeshop-page-description">
                                <div class="stripeshop-page-quantity">
                                    <label :for="'mini-shop-cart-' + item.article.article_number">{{ __('mini-shop::shop.quantity') }}</label>
                                    <input type="number" min="1" max="99" :id="'mini-shop-cart-' + item.article.article_number"
                                        :value="item.quantity" @change="setQuantity(item, $event.target.value)">
                                </div>
                            </div>
                        </div>

                        <div class="stripeshop-page-buy">
                            <h5 class="stripeshop-page-title" x-text="item.readable_total"></h5>
                            <h6 x-show="item.quantity > 1">(<span x-text="item.quantity"></span> &times; <span x-text="item.readable_price"></span>)</h6>
                            <div class="stripeshop-page-button">
                                <button type="button" class="stripeshop-button" @click="$store.miniShop.remove(item)" :disabled="$store.miniShop.busy">
                                    {{ $removeItemText ?? __('mini-shop::shop.remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="stripeshop-page-total">
                <h5>{{ $totalText ?? __('mini-shop::shop.total') }}: <span x-text="$store.miniShop.cart.readable_subtotal"></span></h5>
            </div>

            <div class="stripeshop-page-buttons">
                <a href="{{ $continueShoppingUrl ?? config('mini-shop.urls.shop') }}" class="stripeshop-button is-large is-secondary">
                    {{ $continueShoppingText ?? __('mini-shop::shop.continue_shopping') }}
                </a>
                <a href="{{ $checkoutUrl ?? config('mini-shop.urls.checkout') }}" class="stripeshop-button is-large">
                    {{ $checkoutText ?? __('mini-shop::shop.checkout') }}
                </a>
            </div>
        </div>
    </template>
</div>
