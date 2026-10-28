@foreach ($order['articles'] as $article)
<p>
    <strong>{{ $article['quantity'] }} &times; {{ $article['product_title'] }}</strong><br>
    @if (! empty($article['attributes'])){{ $article['attributes'] }}<br>@endif
    {{ __('mini-shop::shop.mail.article_number') }}: {{ $article['article_number'] }}<br>
    {{ __('mini-shop::shop.mail.unit_price') }}: {{ $article['readable_price'] }}<br>
    {{ __('mini-shop::shop.mail.sum') }}: <strong>{{ $article['readable_total'] }}</strong>
</p>
<hr>
@endforeach
<p>
    {{ __('mini-shop::shop.shipping_costs') }}: {{ $order['readable_shipcost'] }}
    @if (($order['deposit'] ?? 0) > 0)<br>{{ __('mini-shop::shop.deposit') }}: {{ $order['readable_deposit'] }}@endif
</p>
<hr>
<p>
    <strong>{{ __('mini-shop::shop.mail.order_sum') }}: {{ $order['readable_total'] }}</strong><br>
    <small>{{ __('mini-shop::shop.mail.already_paid') }}</small>
</p>
<hr>
<p>
    <strong>{{ __('mini-shop::shop.mail.order_number') }}:</strong> {{ $order['order_number'] }}<br>
    <strong>{{ __('mini-shop::shop.email') }}:</strong> {{ $order['invoice_email'] }}
    @if (! empty($order['invoice_phone']))<br><strong>{{ __('mini-shop::shop.phone') }}:</strong> {{ $order['invoice_phone'] }}@endif
</p>
@foreach (['invoice' => 'invoice_address', 'shipping' => 'shipping_address'] as $type => $label)
<p>
    <strong>{{ __('mini-shop::shop.'.$label) }}:</strong><br>
    {{ $order[$type.'_name'] ?? '' }}<br>
    {{ $order[$type.'_street'] ?? '' }}<br>
    @if (! empty($order[$type.'_street2'])){{ $order[$type.'_street2'] }}<br>@endif
    {{ $order[$type.'_postal_code'] ?? '' }} {{ $order[$type.'_city'] ?? '' }}<br>
    {{ $order[$type.'_country'] ?? '' }}
</p>
@endforeach
