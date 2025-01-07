<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Writer;

use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentWriter implements PaymentWriterInterface
{
    public function __construct(
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $this->persistStatusHistory($paymentTransfer);

        return $this->appPaymentEntityManager->createPayment($paymentTransfer);
    }

    public function updatePayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $this->persistStatusHistory($paymentTransfer);

        return $this->appPaymentEntityManager->updatePayment($paymentTransfer);
    }

    protected function persistStatusHistory(PaymentTransfer $paymentTransfer): void
    {
        if (!$this->hasStatusChanged($paymentTransfer)) {
            return;
        }

        $this->appPaymentEntityManager->savePaymentStatusHistory($paymentTransfer);
    }

    protected function hasStatusChanged(PaymentTransfer $paymentTransfer): bool
    {
        // In case, we don't have an origin payment, we consider the status has changed as we have a new payment.
        if (!$paymentTransfer->getOriginPayment() instanceof PaymentTransfer) {
            return true;
        }

        return $paymentTransfer->getOriginPayment()->getStatus() !== $paymentTransfer->getStatus();
    }
}
