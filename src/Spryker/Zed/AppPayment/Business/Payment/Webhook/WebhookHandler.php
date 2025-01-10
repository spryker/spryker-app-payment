<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use InvalidArgumentException;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\WebhookHandlerSelector;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookHandler
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected WebhookRequestExtender $webhookRequestExtender,
        protected WebhookMessageSender $webhookMessageSender,
        protected WebhookHandlerSelector $webhookHandlerSelector
    ) {
    }

    public function handleWebhook(WebhookRequestTransfer $webhookRequestTransfer, WebhookResponseTransfer $webhookResponseTransfer): WebhookResponseTransfer
    {
        try {
            $webhookRequestTransfer = $this->extendWebhookRequestTransfer($webhookRequestTransfer);

            $webhookResponseTransfer = $this->validateWebhookRequest($webhookRequestTransfer, $webhookResponseTransfer);

            if ($webhookResponseTransfer->getIsSuccessful() === false) {
                return $webhookResponseTransfer;
            }

            if ($webhookRequestTransfer->getAbortHandling() === true) {
                // This will result in a 200 OK Response send back to the caller of the webhook endpoint.
                return $webhookResponseTransfer->setIsSuccessful(true);
            }

            $webhookResponseTransfer = $this->appPaymentPlatformPlugin->handleWebhook($webhookRequestTransfer, $webhookResponseTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $webhookRequestTransfer->getPayment() instanceof PaymentTransfer ? $webhookRequestTransfer->getPayment()->getTransactionIdOrFail() : '',
                PaymentTransfer::TENANT_IDENTIFIER => $webhookRequestTransfer->getPayment() instanceof PaymentTransfer ? $webhookRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail() : '',
            ]);
            $webhookResponseTransfer = new WebhookResponseTransfer();
            $webhookResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $webhookResponseTransfer;
        }

        // Return a failed response when response transfer is not successful or the WebhookRequest is explicitly set to not handled.
        // The message should already be set in the payment platform plugin.
        if ($webhookResponseTransfer->getIsSuccessful() !== true || $webhookResponseTransfer->getIsHandled() === false) {
            return $webhookResponseTransfer;
        }

        /** @phpstan-var \Generated\Shared\Transfer\WebhookResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($webhookRequestTransfer, $webhookResponseTransfer): \Generated\Shared\Transfer\WebhookResponseTransfer {
            $webhookResponseTransfer = $this->webhookHandlerSelector
                ->selectWebhookHandler($webhookRequestTransfer)
                ->handleWebhook($webhookRequestTransfer, $webhookResponseTransfer);

            $this->webhookMessageSender->determineAndSendMessage($webhookRequestTransfer);

            return $webhookResponseTransfer;
        });
    }

    protected function extendWebhookRequestTransfer(WebhookRequestTransfer $webhookRequestTransfer): WebhookRequestTransfer
    {
        $webhookRequestTransfer->setTransactionId($this->getTransactionIdOrFail($webhookRequestTransfer));

        return $this->webhookRequestExtender->extendWebhookRequestTransfer($webhookRequestTransfer);
    }

    private function getTransactionIdOrFail(WebhookRequestTransfer $webhookRequestTransfer): string
    {
        if ($webhookRequestTransfer->getTransactionId() !== null && $webhookRequestTransfer->getTransactionId() !== '') {
            return $webhookRequestTransfer->getTransactionId();
        }

        throw new InvalidArgumentException(MessageBuilder::getRequestTransactionIdIsMissingOrEmpty());
    }

    private function validateWebhookRequest(
        WebhookRequestTransfer $webhookRequestTransfer,
        WebhookResponseTransfer $webhookResponseTransfer
    ): WebhookResponseTransfer {
        $appConfigTransfer = $webhookRequestTransfer->getAppConfig();

        if (!$appConfigTransfer instanceof AppConfigTransfer || $appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED) {
            $webhookResponseTransfer
                ->setIsSuccessful(false)
                ->setHttpStatusCode(Response::HTTP_FORBIDDEN)
                ->setMessage(MessageBuilder::tenantIsDisconnected());
        }

        return $webhookResponseTransfer;
    }
}
