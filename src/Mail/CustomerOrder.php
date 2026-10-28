<?php

namespace Bastihilger\MiniStatamicShop\Mail;

use Bastihilger\MiniStatamicShop\Orders;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerOrder extends Mailable
{
    public function __construct(public $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mini-shop::shop.mail.customer_subject', ['shop' => config('mini-shop.shop_name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mini-shop::emails.customer-order',
            with: [
                'order' => Orders::forMail($this->order),
                'shopName' => config('mini-shop.shop_name'),
                'footerText' => config('mini-shop.email_footer_text'),
            ],
        );
    }
}
