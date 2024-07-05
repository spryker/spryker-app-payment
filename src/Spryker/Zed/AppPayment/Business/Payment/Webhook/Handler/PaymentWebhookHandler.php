<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler;

use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatusTransitionValidator;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Throwable;

class PaymentWebhookHandler implements WebhookHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected PaymentStatusTransitionValidator $paymentStatusTransitionValidator
    ) {
    }

    public function handleWebhook(
        WebhookRequestTransfer $webhookRequestTransfer,
        WebhookResponseTransfer $webhookResponseTransfer
    ): WebhookResponseTransfer {
        $paymentTransfer = $webhookRequestTransfer->getPaymentOrFail();

        $targetStatus = $webhookResponseTransfer->getPaymentStatusOrFail();

        $sourceStatus = $paymentTransfer->getStatusOrFail();

        if ($sourceStatus === $targetStatus) {
            $this->getLogger()->info(MessageBuilder::paymentStatusAlreadyInStatus($targetStatus), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ]);

            return $webhookResponseTransfer->setIsSuccessful(true);
        }

        if (!$this->paymentStatusTransitionValidator->isTransitionAllowed($sourceStatus, $targetStatus)) {
            return $webhookResponseTransfer->setIsSuccessful(false)
                ->setMessage(MessageBuilder::paymentStatusTransitionNotAllowed($sourceStatus, $targetStatus));
        }

        $paymentTransfer->setStatus($webhookResponseTransfer->getPaymentStatus());

        try {
            $this->appPaymentEntityManager->savePayment($paymentTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ]);

            return $webhookResponseTransfer->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());
        }

        return $webhookResponseTransfer;
    }
}
