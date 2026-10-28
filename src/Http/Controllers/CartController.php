<?php

namespace Bastihilger\MiniStatamicShop\Http\Controllers;

use Bastihilger\MiniStatamicShop\Cart;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    public function __construct(protected Cart $cart)
    {
    }

    public function show()
    {
        return $this->cart->toArray();
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|string',
            'article_number' => 'required|string',
            'quantity' => 'nullable|integer|min:1|max:'.Cart::MAX_QUANTITY,
        ]);

        $this->cart->add($data['product_id'], $data['article_number'], $data['quantity'] ?? 1);

        return $this->cart->toArray();
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|string',
            'article_number' => 'required|string',
            'quantity' => 'required|integer|min:0|max:'.Cart::MAX_QUANTITY,
        ]);

        $this->cart->update($data['product_id'], $data['article_number'], $data['quantity']);

        return $this->cart->toArray();
    }
}
