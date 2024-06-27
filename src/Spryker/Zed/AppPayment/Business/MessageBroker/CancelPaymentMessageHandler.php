<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Business\Payment\Cancel\CancelPayment;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class CancelPaymentMessageHandler implements CancelPaymentMessageHandlerInterface
{
    public function __construct(
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected TenantIdentifierExtractor $tenantIdentifierExtractor,
        protected CancelPayment $cancelPayment,
        protected MessageSender $messageSender
    ) {
    }

    public function handleCancelPayment(
        CancelPaymentTransfer $cancelPaymentTransfer
    ): void {
        $tenantIdentifier = $this->tenantIdentifierExtractor->getTenantIdentifierFromMessage($cancelPaymentTransfer);

        $paymentTransfer = $this->appPaymentRepository->getPaymentByTenantIdentifierAndOrderReference(
            $tenantIdentifier,
            $cancelPaymentTransfer->getOrderReferenceOrFail(),
        );

        $cancelPaymentRequestTransfer = (new CancelPaymentRequestTransfer())
            ->setTransactionId($paymentTransfer->getTransactionIdOrFail())
            ->setPayment($paymentTransfer);

        $cancelPaymentResponseTransfer = $this->cancelPayment->cancelPayment($cancelPaymentRequestTransfer);

        $this->determineAndSendMessage($paymentTransfer, $cancelPaymentResponseTransfer);
    }

    protected function determineAndSendMessage(
        PaymentTransfer $paymentTransfer,
        CancelPaymentResponseTransfer $cancelPaymentResponseTransfer
    ): void {
        $paymentStatus = $cancelPaymentResponseTransfer->getStatusOrFail();

        match ($paymentStatus) {
            PaymentStatus::STATUS_CANCELED => $this->messageSender->sendPaymentCanceledMessage($paymentTransfer),
            PaymentStatus::STATUS_CANCELLATION_FAILED => $this->messageSender->sendPaymentCancellationFailedMessage($paymentTransfer),
            default => 'Status is not handled',
        };
    }
}
