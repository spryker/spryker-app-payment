<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer;

use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\SeparatorToDash;

class PaymentMethodNormalizer
{
    /**
     * Projects MAY send a value like "Credit Card" as the payment method key we need to normalize this to "credit-card".
     */
    public function normalizePaymentMethodKey(string $paymentMethodKey): string
    {
        $filterChain = new FilterChain();
        $filterChain
            ->attach(new StringToLower())
            ->attach(new SeparatorToDash());

        return $filterChain->filter($paymentMethodKey);
    }
}
