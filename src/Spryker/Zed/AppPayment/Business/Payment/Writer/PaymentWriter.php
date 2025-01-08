<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Writer;

use Generated\Shared\Transfer\PaymentStatusHistoryCriteriaTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentWriter implements PaymentWriterInterface
{
    public function __construct(
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected MessageSender $messageSender
    ) {
    }

    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $this->persistStatusHistory($paymentTransfer);

        $paymentTransfer = $this->appPaymentEntityManager->createPayment($paymentTransfer);
        $paymentTransfer = $this->addPaymentStatusHistoryToDetails($paymentTransfer);

        $this->messageSender->sendPaymentCreatedMessage($paymentTransfer);

        return $paymentTransfer;
    }

    public function updatePayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $this->persistStatusHistory($paymentTransfer);

        $paymentTransfer = $this->appPaymentEntityManager->updatePayment($paymentTransfer);
        $paymentTransfer = $this->addPaymentStatusHistoryToDetails($paymentTransfer);

        $this->messageSender->sendPaymentUpdatedMessage($paymentTransfer);

        return $paymentTransfer;
    }

    protected function addPaymentStatusHistoryToDetails(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $paymentStatusHistoryCriteriaTransfer = new PaymentStatusHistoryCriteriaTransfer();
        $paymentStatusHistoryCriteriaTransfer
            ->setTransactionId($paymentTransfer->getTransactionIdOrFail());

        $paymentStatusHistoryCollectionTransfer = $this->appPaymentRepository->getPaymentStatusHistoryCollection($paymentStatusHistoryCriteriaTransfer);

        $paymentStatusHistories = [];

        $paymentStatusHistoryTransfers = $paymentStatusHistoryCollectionTransfer->getPaymentStatusHistory();

        foreach ($paymentStatusHistoryTransfers as $paymentStatusHistoryTransfer) {
            $paymentStatusHistories[] = [
                'status' => $paymentStatusHistoryTransfer->getStatus(),
                'created' => $paymentStatusHistoryTransfer->getCreatedAt(),
            ];
        }

        $details = $paymentTransfer->getDetails() ?? '{}';
        $detailsArray = json_decode($details, true);
        $detailsArray['paymentStatusHistory'] = $paymentStatusHistories;

        $paymentTransfer->setDetails((string)json_encode($detailsArray));

        return $paymentTransfer;
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
