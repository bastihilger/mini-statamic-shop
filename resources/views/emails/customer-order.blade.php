<h3>{{ __('mini-shop::shop.mail.thanks') }}</h3>

@include('mini-shop::emails._order')

@if ($footerText)
<hr>
<small>{!! nl2br(e($footerText)) !!}</small>
@endif
