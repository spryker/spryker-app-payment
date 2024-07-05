<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;

trait AppPaymentHelperTrait
{
    protected function getPaymentHelper(): AppPaymentHelper
    {
        /** @var \SprykerTest\Shared\AppPayment\Helper\AppPaymentHelper $paymentHelper */
        $paymentHelper = $this->getModule('\\' . AppPaymentHelper::class);

        return $paymentHelper;
    }

    abstract protected function getModule(string $name): Module;
}
