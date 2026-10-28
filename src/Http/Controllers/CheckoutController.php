<?php

namespace Bastihilger\MiniStatamicShop\Http\Controllers;

use Bastihilger\MiniStatamicShop\Checkout;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(protected Checkout $checkout)
    {
    }

    public function start(Request $request)
    {
        $address = $request->validate($this->rules($request));

        $url = $this->checkout->start($address);

        if (! $url) {
            return response()->json(['message' => __('mini-shop::shop.cart_is_empty')], 422);
        }

        return ['url' => $url];
    }

    protected function rules(Request $request): array
    {
        $countries = array_keys(config('mini-shop.countries'));

        $rules = [
            'email' => 'required|email',
            'shipping_same_as_invoice' => 'boolean',
        ];

        $types = $request->boolean('shipping_same_as_invoice', true) ? ['invoice'] : ['invoice', 'shipping'];

        foreach ($types as $type) {
            $rules += [
                "{$type}.salutation" => ['required', Rule::in(config('mini-shop.salutations'))],
                "{$type}.first_name" => 'required|string|max:100',
                "{$type}.last_name" => 'required|string|max:100',
                "{$type}.street" => 'required|string|max:200',
                "{$type}.street2" => 'nullable|string|max:200',
                "{$type}.postcode" => 'required|string|max:20',
                "{$type}.city" => 'required|string|max:100',
                "{$type}.country" => ['required', Rule::in($countries)],
                "{$type}.phone" => 'nullable|string|max:50',
            ];
        }

        return $rules;
    }
}
