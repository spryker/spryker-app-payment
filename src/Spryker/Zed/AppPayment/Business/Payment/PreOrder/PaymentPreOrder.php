<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\PreOrder;

use Generated\Shared\Transfer\CancelPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookInboxCriteriaTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppWebhookFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformCancelPreOrderPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformConfirmPreOrderPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentPreOrder
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentToAppWebhookFacadeInterface $appPaymentToAppWebhookFacade,
        protected MessageSender $messageSender,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer {
        try {
            $confirmPreOrderPaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($confirmPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail()));

            $paymentTransfer = $this->appPaymentRepository->getPaymentByTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionIdOrFail());
            $paymentTransfer->setOrderReference($confirmPreOrderPaymentRequestTransfer->getOrderReference());

            $confirmPreOrderPaymentRequestTransfer->setPayment($paymentTransfer);

            // When the payment platform plugin does not implement the `AppPreOrderPaymentPlatformPluginInterface` we assume there is no further action from the PSP implementation
            // needed, and we can simply update the Payment with the missing orderReference.
            $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
            $confirmPreOrderPaymentResponseTransfer
                ->setIsSuccessful(true)
                ->setStatus(PaymentStatus::STATUS_AUTHORIZED);

            if ($this->appPaymentPlatformPlugin instanceof AppPaymentPlatformConfirmPreOrderPluginInterface) {
                $confirmPreOrderPaymentResponseTransfer = $this->appPaymentPlatformPlugin->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);
            }
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TENANT_IDENTIFIER => $confirmPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
            $confirmPreOrderPaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $confirmPreOrderPaymentResponseTransfer;
        }

        /** @phpstan-var \Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer) {
            $this->savePayment($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer);

            // In case of pre-order payment we may have unprocessed webhook requests persisted, and we must process them here
            $webhookInboxCriteriaTransfer = new WebhookInboxCriteriaTransfer();

            // Unprocessed webhooks will be persisted by the transaction id
            $webhookInboxCriteriaTransfer->addIdentifier($confirmPreOrderPaymentRequestTransfer->getTransactionIdOrFail());

            $this->appPaymentToAppWebhookFacade->processUnprocessedWebhooks($webhookInboxCriteriaTransfer);

            $this->sendMessages($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer);

            return $confirmPreOrderPaymentResponseTransfer;
        });
    }

    public function cancelPreOrderPayment(
        CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer
    ): CancelPreOrderPaymentResponseTransfer {
        try {
            $cancelPreOrderPaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($cancelPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail()));
            $cancelPreOrderPaymentRequestTransfer->setPayment($this->appPaymentRepository->getPaymentByTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionIdOrFail()));

            // When the payment platform plugin does not implement the `AppPreOrderPaymentPlatformPluginInterface` we assume there is no further action from the PSP implementation
            // needed, and we can simply delete the Payment.
            $cancelPreOrderPaymentResponseTransfer = new CancelPreOrderPaymentResponseTransfer();
            $cancelPreOrderPaymentResponseTransfer
                ->setIsSuccessful(true);

            if ($this->appPaymentPlatformPlugin instanceof AppPaymentPlatformCancelPreOrderPluginInterface) {
                $cancelPreOrderPaymentResponseTransfer = $this->appPaymentPlatformPlugin->cancelPreOrderPayment($cancelPreOrderPaymentRequestTransfer);
            }
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TENANT_IDENTIFIER => $cancelPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $cancelPreOrderPaymentResponseTransfer = new CancelPreOrderPaymentResponseTransfer();
            $cancelPreOrderPaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $cancelPreOrderPaymentResponseTransfer;
        }

        /** @phpstan-var \Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($cancelPreOrderPaymentRequestTransfer, $cancelPreOrderPaymentResponseTransfer) {
            $this->deletePayment($cancelPreOrderPaymentRequestTransfer);

            // In case of pre-order payment we may have unprocessed webhook requests persisted and we must delete them here
            $webhookInboxCriteriaTransfer = new WebhookInboxCriteriaTransfer();

            // Unprocessed webhooks will be persisted by the transaction id
            $webhookInboxCriteriaTransfer->addIdentifier($cancelPreOrderPaymentRequestTransfer->getTransactionIdOrFail());

            $this->appPaymentToAppWebhookFacade->deleteWebhooks($webhookInboxCriteriaTransfer);

            return $cancelPreOrderPaymentResponseTransfer;
        });
    }

    protected function savePayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer,
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): void {
        $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPaymentOrFail();
        $paymentTransfer
            ->setOrderReference($confirmPreOrderPaymentRequestTransfer->getOrderReference())
            ->setStatus($confirmPreOrderPaymentResponseTransfer->getStatus())
            ->setQuote($confirmPreOrderPaymentRequestTransfer->getOrderData());

        // Covers the case when the implementation alters the payment transfer
        if ($confirmPreOrderPaymentResponseTransfer->getPayment() instanceof PaymentTransfer) {
            $paymentTransfer->fromArray($confirmPreOrderPaymentResponseTransfer->getPayment()->modifiedToArray());
        }

        $this->appPaymentEntityManager->savePayment($paymentTransfer);
    }

    protected function deletePayment(
        CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer
    ): void {
        $paymentCollectionDeleteCriteriaTransfer = new PaymentCollectionDeleteCriteriaTransfer();
        $paymentCollectionDeleteCriteriaTransfer
            ->setTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionIdOrFail())
            ->setTenantIdentifier($cancelPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail());

        $this->appPaymentEntityManager->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);
    }

    protected function sendMessages(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer,
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): void {
        $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPaymentOrFail();

        $this->messageSender->sendPaymentUpdatedMessage($paymentTransfer);

        if ($confirmPreOrderPaymentResponseTransfer->getIsSuccessful() === true) {
            $this->messageSender->sendPaymentAuthorizedMessage($paymentTransfer);

            return;
        }

        $this->messageSender->sendPaymentAuthorizationFailedMessage($paymentTransfer);
    }
}
