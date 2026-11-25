<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentException;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Test-mode gateway so the whole flow works with no external account and no real money.
 *  - 4242 4242 4242 4242 (or any valid-looking number) succeeds
 *  - 4000 0000 0000 0002 is declined
 */
class MockGateway implements PaymentGateway
{
    public function charge(Order $order, array $card): string
    {
        $digits = preg_replace('/\D/', '', $card['number'] ?? '') ?? '';

        if (strlen($digits) < 12) {
            throw new PaymentException('Enter a valid card number (try 4242 4242 4242 4242).');
        }

        if ($digits === '4000000000000002') {
            throw new PaymentException('Your card was declined. Try a different card.');
        }

        return 'mock_'.Str::lower(Str::random(18));
    }
}
