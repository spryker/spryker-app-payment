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
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Throwable;

class PaymentRefundWebhookHandler implements WebhookHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager
    ) {
    }

    public function handleWebhook(
        WebhookRequestTransfer $webhookRequestTransfer,
        WebhookResponseTransfer $webhookResponseTransfer
    ): WebhookResponseTransfer {
        $paymentRefundTransfer = $webhookRequestTransfer->getRefundOrFail();
        $paymentRefundStatus = $webhookResponseTransfer->getRefundStatusOrFail();
        $sourceStatus = $paymentRefundTransfer->getStatusOrFail();

        if ($sourceStatus === $paymentRefundStatus) {
            return $webhookResponseTransfer->setIsSuccessful(true);
        }

        $paymentRefundTransfer->setStatus($paymentRefundStatus);

        try {
            $this->appPaymentEntityManager->updatePaymentRefund($paymentRefundTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $paymentRefundTransfer->getTransactionId(),
                PaymentTransfer::TENANT_IDENTIFIER => $webhookRequestTransfer->getPaymentOrFail()->getTenantIdentifier(),
            ]);

            return $webhookResponseTransfer->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());
        }

        return $webhookResponseTransfer;
    }
}
