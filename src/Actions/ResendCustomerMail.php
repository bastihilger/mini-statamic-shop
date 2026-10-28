<?php

namespace Bastihilger\MiniStatamicShop\Actions;

use Bastihilger\MiniStatamicShop\Orders;
use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;

class ResendCustomerMail extends Action
{
    public static function title()
    {
        return __('mini-shop::shop.cp.resend_customer_mail');
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry
            && $item->collectionHandle() === config('mini-shop.collections.orders')
            && $item->published();
    }

    public function authorize($user, $item)
    {
        return $user->can('edit', $item);
    }

    public function run($items, $values)
    {
        $items->each(fn ($order) => app(Orders::class)->sendCustomerMail($order, true));

        return __('mini-shop::shop.cp.mail_sent');
    }
}
