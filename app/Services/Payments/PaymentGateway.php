<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use App\Models\Order;

interface PaymentGateway
{
    /**
     * Charge an order. Returns a provider reference on success.
     *
     * @param  array{number?: string, name?: string, expiry?: string, cvc?: string}  $card
     *
     * @throws PaymentException when the charge is declined
     */
    public function charge(Order $order, array $card): string;
}
