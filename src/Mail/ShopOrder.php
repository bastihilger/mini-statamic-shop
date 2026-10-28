<?php

namespace Bastihilger\MiniStatamicShop\Mail;

use Bastihilger\MiniStatamicShop\Orders;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ShopOrder extends Mailable
{
    public function __construct(public $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mini-shop::shop.mail.shop_subject', [
                'shop' => config('mini-shop.shop_name'),
                'number' => $this->order->get('order_number'),
            ]),
            replyTo: array_filter([$this->order->get('invoice_email')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mini-shop::emails.shop-order',
            with: ['order' => Orders::forMail($this->order)],
        );
    }
}
