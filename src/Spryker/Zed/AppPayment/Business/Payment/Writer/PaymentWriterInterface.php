<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Writer;

use Generated\Shared\Transfer\PaymentTransfer;

interface PaymentWriterInterface
{
    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer;

    public function updatePayment(PaymentTransfer $paymentTransfer): PaymentTransfer;
}
