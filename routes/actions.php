<?php

use Bastihilger\MiniStatamicShop\Http\Controllers\CartController;
use Bastihilger\MiniStatamicShop\Http\Controllers\CheckoutController;
use Bastihilger\MiniStatamicShop\Http\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('cart', [CartController::class, 'show'])->name('mini-shop.cart');
Route::post('cart/add', [CartController::class, 'add'])->name('mini-shop.cart.add');
Route::post('cart/update', [CartController::class, 'update'])->name('mini-shop.cart.update');
Route::post('checkout', [CheckoutController::class, 'start'])->name('mini-shop.checkout');
Route::post('webhook', WebhookController::class)
    ->withoutMiddleware(PreventRequestForgery::class)
    ->name('mini-shop.webhook');
