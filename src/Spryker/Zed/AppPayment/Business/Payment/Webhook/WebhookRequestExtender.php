<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook;

use Generated\Shared\Transfer\WebhookRequestTransfer;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class WebhookRequestExtender
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppConfigLoader $appConfigLoader,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function extendWebhookRequestTransfer(WebhookRequestTransfer $webhookRequestTransfer): WebhookRequestTransfer
    {
        $paymentTransfer = $this->appPaymentRepository->getPaymentByTransactionId($webhookRequestTransfer->getTransactionIdOrFail());
        $appConfigTransfer = $this->appConfigLoader->loadAppConfig($paymentTransfer->getTenantIdentifierOrFail());

        $webhookRequestTransfer->setPaymentOrFail($paymentTransfer);
        $webhookRequestTransfer->setAppConfigOrFail($appConfigTransfer);

        if ($webhookRequestTransfer->getTypeOrFail() === WebhookDataType::REFUND) {
            $webhookRequestTransfer->setRefundOrFail(
                $this->appPaymentRepository->getRefundByRefundId($webhookRequestTransfer->getRefundOrFail()->getRefundIdOrFail()),
            );
        }

        return $webhookRequestTransfer;
    }
}
