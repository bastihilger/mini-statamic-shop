<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shop
    |--------------------------------------------------------------------------
    |
    | The shop name is used in mail subjects. Order mails go to every address
    | in "order_recipients" (comma separated). The footer text is appended to
    | the customer mail.
    |
    */

    'shop_name' => env('MINI_SHOP_NAME', 'E-Shop'),

    'order_recipients' => env('MINI_SHOP_ORDER_RECIPIENTS', ''),

    'email_footer_text' => '',

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    |
    | The payment methods offered on Stripe Checkout are the ones enabled in
    | the Stripe Dashboard (Settings → Payment methods).
    |
    */

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'currency' => 'eur',

    // Shown after prices, e.g. "36,00 €" or "36,00 EUR".
    'currency_symbol' => '€',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    'urls' => [
        'shop' => '/shop',
        'cart' => '/warenkorb',
        'checkout' => '/kasse',
        'thankyou' => '/danke',
        'cancel' => '/abbruch',
        'shipping_costs' => '/versandkosten',
        'terms' => '/agb',
    ],

    /*
    |--------------------------------------------------------------------------
    | Collections and taxonomies
    |--------------------------------------------------------------------------
    |
    | Set "categories" to a taxonomy handle to filter {{ mini_shop:products }}
    | by the ?category= query parameter (the first term is the default).
    |
    */

    'collections' => [
        'products' => 'products',
        'orders' => 'orders',
        'shipping_rates' => 'shipping_rates',
        'deposits' => 'deposits',
    ],

    'categories' => null,

    /*
    |--------------------------------------------------------------------------
    | Product fields
    |--------------------------------------------------------------------------
    |
    | Field handles on the product blueprint. "articles" is a replicator or
    | grid whose items have an article number, a price and optional
    | attributes (a list of title/value pairs).
    |
    */

    'fields' => [
        'articles' => 'articles',
        'article_number' => 'article_number',
        'price' => 'price',
        'attributes' => 'attributes',
        'image' => 'main_image',
        'description' => 'description',
        'sort' => 'sort_order',
        'category' => 'category',
        'shipping_rate' => 'shipping_rate',
        'deposit' => 'deposit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout form
    |--------------------------------------------------------------------------
    */

    'countries' => [
        'DE' => 'Deutschland',
    ],

    'salutations' => ['Herr', 'Frau'],

    // The heading tag used for the checkout sections (h2, h3, ...).
    'headline_tag' => 'h2',

    /*
    |--------------------------------------------------------------------------
    | Payment check
    |--------------------------------------------------------------------------
    |
    | Open orders are checked against Stripe on the thank-you page, through
    | the webhook (if configured) and on this schedule as a fallback.
    | Needs Laravel's scheduler to run (php artisan schedule:run).
    |
    */

    'check_payments_cron' => '*/10 * * * *',

    /*
    |--------------------------------------------------------------------------
    | Control Panel
    |--------------------------------------------------------------------------
    |
    | Adds a "Shop" section to the CP navigation with links to the shop
    | collections. Disable it to build your own navigation.
    |
    */

    'cp_nav' => true,

];
