<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\MessageContextTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Business\Payment\Capture\PaymentCapturer;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;

class CapturePaymentMessageHandler implements CapturePaymentMessageHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected TenantIdentifierExtractor $tenantIdentifierExtractor,
        protected PaymentCapturer $paymentCapturer,
        protected MessageSender $messageSender
    ) {
    }

    public function handleCapturePayment(
        CapturePaymentTransfer $capturePaymentTransfer
    ): void {
        $tenantIdentifier = $this->tenantIdentifierExtractor->getTenantIdentifierFromMessage($capturePaymentTransfer);

        try {
            $paymentTransfer = $this->appPaymentRepository->getPaymentByTenantIdentifierAndOrderReference(
                $tenantIdentifier,
                $capturePaymentTransfer->getOrderReferenceOrFail(),
            );
        } catch (PaymentByTenantIdentifierAndOrderReferenceNotFoundException $paymentByTenantIdentifierAndOrderReferenceNotFoundException) {
            $this->getLogger()->warning($paymentByTenantIdentifierAndOrderReferenceNotFoundException->getMessage());

            return;
        }

        $capturePaymentRequestTransfer = (new CapturePaymentRequestTransfer())
            ->setTransactionId($paymentTransfer->getTransactionIdOrFail())
            ->setPayment($paymentTransfer);

        $capturePaymentResponseTransfer = $this->paymentCapturer->capturePayment($capturePaymentRequestTransfer);

        $this->determineAndSendMessage($capturePaymentTransfer, $paymentTransfer, $capturePaymentResponseTransfer);
    }

    protected function determineAndSendMessage(
        CapturePaymentTransfer $capturePaymentTransfer,
        PaymentTransfer $paymentTransfer,
        CapturePaymentResponseTransfer $capturePaymentResponseTransfer
    ): void {
        $paymentStatus = $capturePaymentResponseTransfer->getStatusOrFail();

        $messageContextTransfer = $this->buildMessageContextTransfer($capturePaymentTransfer);

        match ($paymentStatus) {
            PaymentStatus::STATUS_CAPTURED => $this->messageSender->sendPaymentCapturedMessage($paymentTransfer, $messageContextTransfer),
            PaymentStatus::STATUS_CAPTURE_FAILED => $this->messageSender->sendPaymentCaptureFailedMessage($paymentTransfer, $messageContextTransfer),
            default => 'do nothing and wait for webhooks',
        };
    }

    /**
     * In case of a partial capture, the message context transfer will contain the order item ids and the amount.
     */
    protected function buildMessageContextTransfer(CapturePaymentTransfer $capturePaymentTransfer): MessageContextTransfer
    {
        $orderItemIds = $capturePaymentTransfer->getOrderItemIds() !== [] ? $capturePaymentTransfer->getOrderItemIds() : null;
        $amount = $capturePaymentTransfer->getAmount() !== null && $capturePaymentTransfer->getAmount() !== 0 ? (string)$capturePaymentTransfer->getAmount() : null;

        $messageContextTransfer = new MessageContextTransfer();
        $messageContextTransfer->setOrderItemsIds($orderItemIds);
        $messageContextTransfer->setAmount($amount);

        return $messageContextTransfer;
    }
}
