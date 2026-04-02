<?php

namespace App\Listeners;

use App\Events\OrderUpdated as OrderUpdatedEvent;
use App\Models\Order;

class PartnerOrderCommissionListener
{
    public function handle(OrderUpdatedEvent $event): void
    {
        $order = $event->order;

        if (!$order->wasChanged('status')) {
            return;
        }

        if ((int) $order->status !== Order::STATUS_COMPLETED) {
            return;
        }

        app('Service\PartnerWalletService')->createCommissionForCompletedOrder($order);
    }
}
