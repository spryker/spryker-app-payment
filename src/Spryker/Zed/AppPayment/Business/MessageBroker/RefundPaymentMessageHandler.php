<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\MessageContextTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteItemTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefunder;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;

class RefundPaymentMessageHandler implements RefundPaymentMessageHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected TenantIdentifierExtractor $tenantIdentifierExtractor,
        protected PaymentRefunder $paymentRefunder,
        protected MessageSender $messageSender
    ) {
    }

    public function handleRefundPayment(RefundPaymentTransfer $refundPaymentTransfer): void
    {
        $tenantIdentifier = $this->tenantIdentifierExtractor->getTenantIdentifierFromMessage($refundPaymentTransfer);

        try {
            $paymentTransfer = $this->appPaymentRepository->getPaymentByTenantIdentifierAndOrderReference(
                $tenantIdentifier,
                $refundPaymentTransfer->getOrderReferenceOrFail(),
            );
        } catch (PaymentByTenantIdentifierAndOrderReferenceNotFoundException $paymentByTenantIdentifierAndOrderReferenceNotFoundException) {
            $this->getLogger()->warning($paymentByTenantIdentifierAndOrderReferenceNotFoundException->getMessage());

            return;
        }

        $refundPaymentRequestTransfer = (new RefundPaymentRequestTransfer())
            ->setTransactionId($paymentTransfer->getTransactionIdOrFail())
            ->setAmount($refundPaymentTransfer->getAmountOrFail())
            ->setCurrencyCode($refundPaymentTransfer->getCurrencyIsoCodeOrFail())
            ->setPayment($paymentTransfer);

        /** @phpstan-var \Generated\Shared\Transfer\OrderItemTransfer $orderItemTransfer */
        foreach ($refundPaymentTransfer->getOrderItems() as $orderItemTransfer) {
            $refundPaymentRequestTransfer->addQuoteItem(
                (new QuoteItemTransfer())
                    ->setIdSalesOrderItem((string)$orderItemTransfer->getOrderItemId())
                    ->setSku($orderItemTransfer->getSku()),
            );
        }

        $refundPaymentResponseTransfer = $this->paymentRefunder->refundPayment($refundPaymentRequestTransfer);

        if ($refundPaymentResponseTransfer->getIsSuccessful() !== true) {
            $refundOrderItemIds = array_map(
                static fn (QuoteItemTransfer $quoteItemTransfer): string => $quoteItemTransfer->getIdSalesOrderItemOrFail(),
                iterator_to_array($refundPaymentRequestTransfer->getQuoteItems()),
            );

            $this->getLogger()->error(sprintf(
                'Refund payment attempt failed for tenant "%s" for orderReference "%s" and order items [%s] with message "%s".',
                $tenantIdentifier,
                $paymentTransfer->getOrderReference(),
                implode(', ', $refundOrderItemIds),
                $refundPaymentResponseTransfer->getMessage(),
            ), [
                RefundPaymentRequestTransfer::TRANSACTION_ID => $refundPaymentRequestTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            ]);
        }

        $this->determineAndSendMessage($paymentTransfer, $refundPaymentRequestTransfer, $refundPaymentResponseTransfer);
    }

    protected function determineAndSendMessage(
        PaymentTransfer $paymentTransfer,
        RefundPaymentRequestTransfer $refundPaymentRequestTransfer,
        RefundPaymentResponseTransfer $refundPaymentResponseTransfer
    ): void {
        $paymentRefundStatus = $refundPaymentResponseTransfer->getStatusOrFail();
        $orderItemIds = array_map(
            static fn (QuoteItemTransfer $quoteItemTransfer): string => $quoteItemTransfer->getIdSalesOrderItemOrFail(),
            iterator_to_array($refundPaymentRequestTransfer->getQuoteItems()),
        );

        $messageContextTransfer = (new MessageContextTransfer())
            ->setAmount((string)$refundPaymentRequestTransfer->getAmountOrFail())
            ->setOrderItemsIds($refundPaymentResponseTransfer->getOrderItemIds() !== [] ? $refundPaymentResponseTransfer->getOrderItemIds() : $orderItemIds);

        match ($paymentRefundStatus) {
            // no message for pending and processing statuses, they are initial phase of refund and OMS should still be in "refund pending" state
            PaymentRefundStatus::PENDING, PaymentRefundStatus::PROCESSING => null,
            PaymentRefundStatus::SUCCEEDED => $this->messageSender->sendPaymentRefundedMessage($paymentTransfer, $messageContextTransfer),
            PaymentRefundStatus::FAILED, PaymentRefundStatus::CANCELED => $this->messageSender->sendPaymentRefundFailedMessage($paymentTransfer, $messageContextTransfer),
            default => $this->getLogger()->warning(sprintf(
                'Unhandled payment refund status "%s" for orderReference "%s" and tenantIdentifier "%s".',
                $paymentRefundStatus,
                $paymentTransfer->getOrderReferenceOrFail(),
                $paymentTransfer->getTenantIdentifierOrFail(),
            ), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ]),
        };
    }
}
