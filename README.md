# Mini Statamic Shop

A small shop for [Statamic](https://statamic.com) 6: products, shipping rates, deposits and orders are ordinary collections, payment happens on [Stripe Checkout](https://stripe.com/payments/checkout). The front end is a handful of Blade views with [Alpine.js](https://alpinejs.dev) components that you can restyle or override.

It started as the "KraenkShop" addon of two Statamic 2 sites and keeps their data format, so existing products and orders keep working.

## Features

- Cart in the session. Prices, shipping and deposits are always read from the entries, never from the browser.
- Products with one or more articles (article number, price, optional attributes such as size or colour).
- Shipping rates with quantity steps ("up to 6 items: 24 €, up to 10: 32 €, more: 42 €"), calculated per rate.
- Deposits (e.g. bottle or can deposit) per product.
- Optional category filter through a taxonomy.
- Stripe Checkout Sessions, one line item per article plus shipping and deposit.
- Orders saved as (unpublished) entries and published once Stripe reports them paid. Payment is checked on the thank-you page, by an optional webhook and by a scheduled command.
- Order mails to the shop and to the customer, with CP actions to resend them.

## Installation

```bash
composer require bastihilger/mini-statamic-shop
php artisan vendor:publish --tag=mini-shop-config
php artisan vendor:publish --tag=mini-shop          # front-end script, only if you don't bundle it yourself
```

Set your Stripe keys in `.env`:

```dotenv
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...   # optional
MINI_SHOP_ORDER_RECIPIENTS=shop@example.com
```

Laravel's scheduler must run (`php artisan schedule:run` every minute) so open orders are checked against Stripe. Orders are checked for 21 days, long enough for delayed payment methods such as SEPA direct debit.

The payment methods offered on Stripe Checkout are the ones enabled in the Stripe Dashboard (Settings → Payment methods).

### Collections

Create four collections (the handles are configurable in `config/mini-shop.php`):

| Config key | Default handle | Purpose |
|---|---|---|
| `collections.products` | `products` | Products, each with an `articles` replicator/grid (`article_number`, `price`, `attributes`) |
| `collections.orders` | `orders` | Orders (written by the shop) |
| `collections.shipping_rates` | `shipping_rates` | Shipping rates with a `steps` grid (`max_items`, `cost`) |
| `collections.deposits` | `deposits` | Deposits with a `cost` field in cents per item |

Products link to a shipping rate and a deposit through entries fields (see `fields` in the config). A read-only order blueprint is in `resources/stubs/blueprints/order.yaml`.

### Front end

Load the script before Alpine starts and render the tags where you need them:

```antlers
<head>
    {{ mini_shop:scripts }}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
</head>

{{ mini_shop:cart_link }}

{{ mini_shop:products }}
    <h2>{{ title }}</h2>
    {{ mini_shop:buy :id="id" }}
{{ /mini_shop:products }}

{{ mini_shop:tinycart }}
{{ mini_shop:cart }}
{{ mini_shop:checkout }}
```

If you bundle your JS with Vite, import the script instead and pass `bundled="true"`:

```js
import Alpine from 'alpinejs';
import '../../vendor/bastihilger/mini-statamic-shop/resources/dist/mini-shop.js';

Alpine.start();
```

```antlers
{{ mini_shop:scripts bundled="true" }}
```

| Tag | Output |
|---|---|
| `mini_shop:products` | Loop over published products, sorted by the sort field and title. Filters by `?category=` when `categories` is set. |
| `mini_shop:product_count`, `mini_shop:cart_count` | Numbers |
| `mini_shop:buy :id="id"` | Price, article select, quantity and "add to cart" |
| `mini_shop:tinycart` | Compact cart |
| `mini_shop:cart_link` | Link to the cart with the item count |
| `mini_shop:cart` | Editable cart (texts and URLs can be overridden with parameters) |
| `mini_shop:checkout` | Order summary, email and address form, then redirect to Stripe. Redirects to the cart when it is empty. |
| `mini_shop:scripts` | Cart state, endpoints and texts for the Alpine components |

To change the markup, publish the views to `resources/views/vendor/mini-shop`. The texts are in `lang/{de,en}/shop.php` and can be overridden in `lang/vendor/mini-shop`.

### Stripe webhook (optional)

Point a webhook for `checkout.session.completed` and `checkout.session.async_payment_succeeded` at `https://your-site/!/mini-shop/webhook` and set `STRIPE_WEBHOOK_SECRET`.

## License

MIT
