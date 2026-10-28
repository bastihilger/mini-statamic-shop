<div class="stripeshop-buy" x-data="miniShopBuy({{ Js::from($product) }})">
    <div class="stripeshop-buy-prices">
        <div class="stripeshop-buy-price">
            <span x-text="article ? article.readable_price : ''">{{ $product['articles'][0]['readable_price'] ?? '' }}</span>
            <span class="stripeshop-buy-price-currency">{{ config('mini-shop.currency_symbol') }}</span>
        </div>

        @if (count($product['articles']) > 1)
            <div class="stripeshop-buy-select">
                <select x-model="articleNumber" aria-label="{{ __('mini-shop::shop.quantity') }}">
                    @foreach ($product['articles'] as $article)
                        <option value="{{ $article['article_number'] }}">
                            {{ collect($article['attributes'])->map(fn ($a) => $a['title'].': '.$a['value'])->implode(' / ') ?: $article['article_number'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="stripeshop-buy-quantity">
            <label for="mini-shop-quantity-{{ $product['id'] }}">{{ __('mini-shop::shop.quantity') }}</label>
            <input id="mini-shop-quantity-{{ $product['id'] }}" type="number" x-model.number="quantity" min="1" max="99">
        </div>

        <div class="stripeshop-buy-button" x-show="!added">
            <button type="button" class="stripeshop-button" @click="add()" :disabled="$store.miniShop.busy">
                {{ __('mini-shop::shop.buy_button') }}
            </button>
        </div>

        <div class="stripeshop-buy-message" x-show="added" x-cloak role="status">
            {{ __('mini-shop::shop.add_to_cart_success') }}
        </div>
    </div>
</div>
