<?php

namespace Bastihilger\MiniStatamicShop\Commands;

use Bastihilger\MiniStatamicShop\Orders;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

class CheckPayments extends Command
{
    use RunsInPlease;

    protected $signature = 'mini-shop:check-payments';

    protected $description = 'Check open shop orders against Stripe and fulfill the paid ones';

    public function handle(Orders $orders): void
    {
        $this->info($orders->checkOpen().' order(s) paid.');
    }
}
