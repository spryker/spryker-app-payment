<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler;

use Generated\Shared\Transfer\WebhookRequestTransfer;
use InvalidArgumentException;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;

class WebhookHandlerSelector
{
    public function __construct(
        protected PaymentWebhookHandler $paymentWebhookHandler,
        protected PaymentRefundWebhookHandler $paymentRefundWebhookHandler
    ) {
    }

    public function selectWebhookHandler(
        WebhookRequestTransfer $webhookRequestTransfer
    ): WebhookHandlerInterface {
        return match ($webhookRequestTransfer->getType()) {
            WebhookDataType::PAYMENT => $this->paymentWebhookHandler,
            WebhookDataType::REFUND => $this->paymentRefundWebhookHandler,
            default => throw new InvalidArgumentException('Unsupported webhook data type'),
        };
    }
}
