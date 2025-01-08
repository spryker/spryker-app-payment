<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Writer;

use DateTime;
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

        $details = $paymentTransfer->getDetails() ?? '{}';
        $detailsArray = json_decode($details, true);

        /**
         * We need to create a table like structure on the receiving side as we can only display key: value pairs.
         *
         * @example
         * | External status "new" | 2025-01-01 12:00:00 |
         * | External status "authorized" | 2025-01-01 12:00:01 |
         *
         * For this, we pad the status key with spaces to have a fixed length.
         */
        $padLength = 50; // Includes 18 characters for "External status """.

        $paymentStatusHistoryTransfers = $paymentStatusHistoryCollectionTransfer->getPaymentStatusHistory();

        foreach ($paymentStatusHistoryTransfers as $paymentStatusHistoryTransfer) {
            $statusText = sprintf('External status "%s"', $paymentStatusHistoryTransfer->getStatus());
            $dateTime = new DateTime($paymentStatusHistoryTransfer->getCreatedAtOrFail());
            $formattedDateTime = $dateTime->format('Y-m-d H:i:s');
            $paddedDateTime = str_pad($formattedDateTime, $padLength - mb_strlen($statusText), ' ', STR_PAD_LEFT);

            $detailsArray[$statusText] = $paddedDateTime;
        }

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
