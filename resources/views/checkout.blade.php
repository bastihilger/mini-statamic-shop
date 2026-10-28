@php($h = config('mini-shop.headline_tag', 'h2'))
<div x-data="miniShopCheckout({{ Js::from($address) }}, {{ Js::from($fields) }})">

    {{-- Order summary --}}
    <div class="stripeshop-section">
        <{{ $h }} class="stripeshop-headline">{{ __('mini-shop::shop.your_order') }}:</{{ $h }}>

        <div class="stripeshop-checkout-products">
            @foreach ($cart['items'] as $item)
                <div class="stripeshop-checkout-product">
                    <div class="stripeshop-checkout-product-text">
                        <div class="stripeshop-checkout-product-title">
                            {{ $item['quantity'] }} &times; {{ $item['product']['title'] }}
                            @foreach ($item['article']['attributes'] as $attribute)
                                / {{ $attribute['title'] }}: {{ $attribute['value'] }}
                            @endforeach
                        </div>
                    </div>
                    <div class="stripeshop-checkout-product-price">{{ $item['readable_price'] }}</div>
                    <div class="stripeshop-checkout-product-total">{{ $item['readable_total'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="stripeshop-checkout-total">{{ __('mini-shop::shop.intermediate_sum') }}: {{ $cart['readable_subtotal'] }}</div>
        <div class="stripeshop-checkout-total">
            <a href="{{ config('mini-shop.urls.shipping_costs') }}" target="_blank">{{ __('mini-shop::shop.shipping_costs') }}</a>: {{ $cart['readable_shipping'] }}
        </div>
        @if ($cart['deposit'] > 0)
            <div class="stripeshop-checkout-total">{{ __('mini-shop::shop.deposit') }}: {{ $cart['readable_deposit'] }}</div>
        @endif
        <div class="stripeshop-checkout-total"><strong>{{ __('mini-shop::shop.total') }}: {{ $cart['readable_total'] }}</strong></div>

        <div class="stripeshop-checkout-buttons">
            <a href="{{ config('mini-shop.urls.cart') }}" class="stripeshop-button is-secondary">{{ __('mini-shop::shop.edit_cart') }}</a>
        </div>
    </div>

    {{-- Email --}}
    <div class="stripeshop-section">
        <{{ $h }} class="stripeshop-headline">{{ __('mini-shop::shop.your_data') }}:</{{ $h }}>

        <div class="stripeshop-form-grid" x-show="section === 'data'">
            <div class="stripeshop-form-col stripeshop-form-container">
                <div class="stripeshop-form-field is-required" :class="{ 'has-errors': error('email') }">
                    <label class="stripeshop-form-label" for="mini-shop-email">{{ __('mini-shop::shop.email') }}</label>
                    <input class="stripeshop-form-input" id="mini-shop-email" type="email" autocomplete="email"
                        x-model="address.email" @input="clearError('email')" @keydown.enter.prevent="validateData()">
                    <div class="stripeshop-form-error" :class="{ 'is-active': error('email') }" x-text="error('email')"></div>
                </div>
            </div>
        </div>

        <div class="stripeshop-checkout-buttons">
            <button type="button" class="stripeshop-button is-secondary" x-show="section !== 'data'" @click="section = 'data'">
                {{ __('mini-shop::shop.edit_data') }}
            </button>
            <button type="button" class="stripeshop-button" x-show="section === 'data'" :class="{ 'is-secondary': !emailIsValid }" @click="validateData()">
                {{ __('mini-shop::shop.continue') }}
            </button>
        </div>
    </div>

    {{-- Address --}}
    <div class="stripeshop-section" x-show="section !== 'data'" x-cloak>
        <{{ $h }} class="stripeshop-headline">{{ __('mini-shop::shop.your_address') }}:</{{ $h }}>

        <div class="stripeshop-form-grid" x-show="section === 'address'">
            <div class="stripeshop-form-col stripeshop-form-container">
                <div class="stripeshop-form-header">{{ __('mini-shop::shop.invoice_address') }}:</div>
                @include('mini-shop::_address-fields', ['type' => 'invoice'])
            </div>

            <div class="stripeshop-form-col stripeshop-form-container">
                <div class="stripeshop-form-header">{{ __('mini-shop::shop.shipping_address') }}:</div>

                <div x-show="!address.shipping_same_as_invoice">
                    @include('mini-shop::_address-fields', ['type' => 'shipping'])
                </div>

                <div class="stripeshop-form-top-buttons">
                    <button type="button" class="stripeshop-button is-full-width"
                        :class="{ 'is-secondary': !address.shipping_same_as_invoice }"
                        @click="address.shipping_same_as_invoice = !address.shipping_same_as_invoice"
                        x-text="address.shipping_same_as_invoice ? @js(__('mini-shop::shop.shipping_not_the_same')) : @js(__('mini-shop::shop.shipping_the_same'))">
                    </button>
                </div>
            </div>
        </div>

        <div class="stripeshop-checkout-terms" x-show="section === 'address'">
            {!! __('mini-shop::shop.by_paying_you_accept_terms', ['url' => e(config('mini-shop.urls.terms'))]) !!}
        </div>

        <p class="stripeshop-form-error is-active" x-show="failed" x-cloak role="alert">{{ __('mini-shop::shop.order_failed') }}</p>

        <div class="stripeshop-checkout-buttons">
            <button type="button" class="stripeshop-button is-secondary" x-show="section === 'redirecting'" @click="section = 'address'">
                {{ __('mini-shop::shop.edit_address') }}
            </button>
            <button type="button" class="stripeshop-button is-medium" x-show="section === 'address'" :class="{ 'is-secondary': !addressIsValid }" @click="validateAddress()">
                {{ __('mini-shop::shop.continue_to_payment') }}
            </button>
        </div>

        <p x-show="section === 'redirecting'" x-cloak role="status">{{ __('mini-shop::shop.one_moment') }}</p>
    </div>
</div>
