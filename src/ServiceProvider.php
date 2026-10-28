<?php

namespace Bastihilger\MiniStatamicShop;

use Bastihilger\MiniStatamicShop\Actions\ResendCustomerMail;
use Bastihilger\MiniStatamicShop\Actions\ResendShopMail;
use Bastihilger\MiniStatamicShop\Commands\CheckPayments;
use Bastihilger\MiniStatamicShop\Http\Middleware\HandleThankYouPage;
use Bastihilger\MiniStatamicShop\Tags\MiniShop;
use Illuminate\Console\Scheduling\Schedule;
use Statamic\Facades\Collection;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $viewNamespace = 'mini-shop';

    protected $tags = [
        MiniShop::class,
    ];

    protected $actions = [
        ResendShopMail::class,
        ResendCustomerMail::class,
    ];

    protected $commands = [
        CheckPayments::class,
    ];

    protected $middlewareGroups = [
        'statamic.web' => [
            HandleThankYouPage::class,
        ],
    ];

    protected $publishables = [
        __DIR__.'/../resources/dist/mini-shop.js' => 'mini-shop.js',
    ];

    protected $publishAfterInstall = true;

    public function register()
    {
        $this->app->singleton(Products::class);
        $this->app->singleton(Orders::class);
        $this->app->scoped(Cart::class);
        $this->app->scoped(Checkout::class);
    }

    public function bootAddon()
    {
        if (config('mini-shop.cp_nav')) {
            Nav::extend(function ($nav) {
                $collections = config('mini-shop.collections');
                $section = __('mini-shop::shop.cp.section');

                foreach (['products' => 'shopping-cart', 'orders' => 'receipt', 'shipping_rates' => 'delivery-truck', 'deposits' => 'recycle'] as $key => $icon) {
                    if ($collection = Collection::findByHandle($collections[$key] ?? '')) {
                        $nav->create(__("mini-shop::shop.cp.{$key}"))
                            ->section($section)
                            ->can('view', $collection)
                            ->route('collections.show', $collection->handle())
                            ->icon($icon);
                    }
                }

                if ($taxonomy = config('mini-shop.categories')) {
                    $nav->create(__('mini-shop::shop.cp.categories'))
                        ->section($section)
                        ->route('taxonomies.show', $taxonomy)
                        ->icon('tags');
                }
            });
        }
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('mini-shop:check-payments')
            ->cron(config('mini-shop.check_payments_cron', '*/10 * * * *'))
            ->withoutOverlapping();
    }
}
