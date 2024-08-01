<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Message;

use ArrayObject;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\EndpointTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\MessageAttributesTransfer;
use Generated\Shared\Transfer\MessageContextTransfer;
use Generated\Shared\Transfer\PaymentAuthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\QuoteItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;

class MessageSender
{
    public function __construct(
        protected AppPaymentToMessageBrokerFacadeInterface $appPaymentToMessageBrokerFacade,
        protected AppPaymentConfig $appPaymentConfig
    ) {
    }

    public function sendAddPaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        // Do not send the message when App is in state "disconnected" or when the app is marked as inactive.
        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED || $appConfigTransfer->getIsActive() === false) {
            return $appConfigTransfer;
        }

        $paymentMethodAppConfigurationTransfer = new PaymentMethodAppConfigurationTransfer();
        $paymentMethodAppConfigurationTransfer->setBaseUrl($this->appPaymentConfig->getGlueBaseUrl());

        $authorizationEndpointTransfer = new EndpointTransfer();
        $authorizationEndpointTransfer
            ->setName('authorization')
            ->setPath('/private/initialize-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $authorizationEndpointTransfer = new EndpointTransfer();
        $authorizationEndpointTransfer
            ->setName('pre-order')
            ->setPath('/private/confirm-pre-order-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $transferEndpointTransfer = new EndpointTransfer();
        $transferEndpointTransfer
            ->setName('transfer')
            ->setPath('/private/payments/transfers'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($transferEndpointTransfer);

        $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
        $addPaymentMethodTransfer
            ->setName($this->appPaymentConfig->getPaymentProviderName())
            ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
            ->setProviderName($this->appPaymentConfig->getPaymentProviderName())
            ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $addPaymentMethodTransfer->setMessageAttributes($this->getMessageAttributes(
            $appConfigTransfer->getTenantIdentifierOrFail(),
            $addPaymentMethodTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($addPaymentMethodTransfer);

        return $appConfigTransfer;
    }

    public function sendDeletePaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        $deletePaymentMethodTransfer = new DeletePaymentMethodTransfer();
        $deletePaymentMethodTransfer
            ->setName($this->appPaymentConfig->getPaymentProviderName())
            ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
            ->setProviderName($this->appPaymentConfig->getPaymentProviderName());

        $deletePaymentMethodTransfer->setMessageAttributes($this->getMessageAttributes(
            $appConfigTransfer->getTenantIdentifierOrFail(),
            $deletePaymentMethodTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($deletePaymentMethodTransfer);

        return $appConfigTransfer;
    }

    public function sendPaymentCapturedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCapturedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCapturedTransfer(), $messageContextTransfer);

        $paymentCapturedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCapturedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCapturedTransfer);
    }

    public function sendPaymentCaptureFailedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCaptureFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCaptureFailedTransfer(), $messageContextTransfer);

        $paymentCaptureFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCaptureFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCaptureFailedTransfer);
    }

    public function sendPaymentAuthorizedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentAuthorizedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentAuthorizedTransfer());

        $paymentAuthorizedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentAuthorizedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentAuthorizedTransfer);
    }

    public function sendPaymentAuthorizationFailedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentAuthorizationFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentAuthorizationFailedTransfer());

        $paymentAuthorizationFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentAuthorizationFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentAuthorizationFailedTransfer);
    }

    public function sendPaymentCanceledMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCanceledTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCanceledTransfer(), $messageContextTransfer);

        $paymentCanceledTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCanceledTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCanceledTransfer);
    }

    public function sendPaymentCancellationFailedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCancellationFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCancellationFailedTransfer(), $messageContextTransfer);

        $paymentCancellationFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCancellationFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCancellationFailedTransfer);
    }

    public function sendPaymentCreatedMessage(
        InitializePaymentRequestTransfer $initializePaymentRequestTransfer,
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): void {
        $quoteTransfer = $initializePaymentRequestTransfer->getOrderDataOrFail();

        $paymentCreatedTransfer = new PaymentCreatedTransfer();
        $paymentCreatedTransfer->fromArray($initializePaymentResponseTransfer->toArray(), true);
        $paymentCreatedTransfer
            ->setEntityReference($quoteTransfer->getOrderReference())
            ->setPaymentReference($initializePaymentResponseTransfer->getTransactionIdOrFail());

        $paymentCreatedTransfer->setMessageAttributes($this->getMessageAttributes(
            $initializePaymentRequestTransfer->getTenantIdentifierOrFail(),
            $paymentCreatedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCreatedTransfer);
    }

    public function sendPaymentUpdatedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentUpdatedTransfer = new PaymentUpdatedTransfer();
        $paymentUpdatedTransfer
            ->setEntityReference($paymentTransfer->getOrderReference())
            ->setPaymentReference($paymentTransfer->getTransactionIdOrFail());

        $paymentUpdatedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentUpdatedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentUpdatedTransfer);
    }

    public function sendPaymentRefundedMessage(
        PaymentTransfer $paymentTransfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): void {
        $paymentRefundedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer(
            $paymentTransfer,
            new PaymentRefundedTransfer(),
            $messageContextTransfer,
        );

        $paymentRefundedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentRefundedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentRefundedTransfer);
    }

    public function sendPaymentRefundFailedMessage(
        PaymentTransfer $paymentTransfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): void {
        $paymentRefundFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer(
            $paymentTransfer,
            new PaymentRefundFailedTransfer(),
            $messageContextTransfer,
        );

        $paymentRefundFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentRefundFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentRefundFailedTransfer);
    }

    /**
     * @template T of \Spryker\Shared\Kernel\Transfer\TransferInterface
     *
     * @param T $transfer
     *
     * @return T
     */
    protected function mapPaymentTransferToPaymentMessageTransfer(
        PaymentTransfer $paymentTransfer,
        TransferInterface $transfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): TransferInterface {
        $quoteTransfer = $paymentTransfer->getQuoteOrFail();

        $paymentData = [];
        $paymentData['orderReference'] = $paymentTransfer->getOrderReferenceOrFail();
        $paymentData['currencyIsoCode'] = $quoteTransfer->getCurrencyCodeOrFail();
        $paymentData['amount'] = $this->getAmount($quoteTransfer, $messageContextTransfer);
        $paymentData['orderItemIds'] = $this->getOrderItemIds($quoteTransfer->getItems(), $messageContextTransfer);

        return $transfer->fromArray($paymentData, true);
    }

    protected function getAmount(QuoteTransfer $quoteTransfer, ?MessageContextTransfer $messageContextTransfer): string
    {
        if ($messageContextTransfer instanceof MessageContextTransfer && $messageContextTransfer->getAmount()) {
            return $messageContextTransfer->getAmount();
        }

        return $quoteTransfer->getGrandTotalOrFail();
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\QuoteItemTransfer> $arrayObject
     *
     * @return array<int, mixed>
     */
    protected function getOrderItemIds(ArrayObject $arrayObject, ?MessageContextTransfer $messageContextTransfer): array
    {
        if ($messageContextTransfer instanceof MessageContextTransfer && $messageContextTransfer->getOrderItemsIds()) {
            return $messageContextTransfer->getOrderItemsIds();
        }

        return array_map(static function (QuoteItemTransfer $quoteItemTransfer): ?string {
            return $quoteItemTransfer->getIdSalesOrderItem();
        }, $arrayObject->getArrayCopy());
    }

    protected function getMessageAttributes(string $tenantIdentifier, string $transferName): MessageAttributesTransfer
    {
        $messageAttributesTransfer = new MessageAttributesTransfer();
        $messageAttributesTransfer
            ->setActorId($this->appPaymentConfig->getAppIdentifier())
            ->setEmitter($this->appPaymentConfig->getAppIdentifier())
            ->setTenantIdentifier($tenantIdentifier)
            ->setStoreReference($tenantIdentifier)
            ->setTransferName($transferName);

        return $messageAttributesTransfer;
    }
}
