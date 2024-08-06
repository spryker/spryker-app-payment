<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method;

use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\EndpointTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationRequestTransfer;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\Message\PaymentMethodMessageSender;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPaymentMethodsPlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethod
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentConfig $appPaymentConfig,
        protected PaymentMethodMessageSender $paymentMethodMessageSender,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function addPaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        // Do not send the message(s) when App is in state "disconnected" or when the app is marked as inactive.
        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED || $appConfigTransfer->getIsActive() === false) {
            return $appConfigTransfer;
        }

        $tenantIdentifier = $appConfigTransfer->getTenantIdentifierOrFail();

        $paymentMethodAppConfigurationTransfer = $this->getDefaultPaymentMethodAppConfiguration();

        if (!($this->appPaymentPlatformPlugin instanceof AppPaymentPaymentMethodsPlatformPluginInterface)) {
            $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
            $addPaymentMethodTransfer
                ->setName($this->appPaymentConfig->getPaymentMethodName())
                ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
                ->setProviderName($this->appPaymentConfig->getPaymentProviderName())
                ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

            $this->paymentMethodMessageSender->sendAddPaymentMethodMessage($addPaymentMethodTransfer, $appConfigTransfer);

            return $appConfigTransfer;
        }

        $paymentMethodConfigurationRequestTransfer = new PaymentMethodConfigurationRequestTransfer();
        $paymentMethodConfigurationRequestTransfer->setAppConfig($appConfigTransfer);

        $paymentMethodCollectionResponseTransfer = $this->appPaymentPlatformPlugin->configurePaymentMethods($paymentMethodConfigurationRequestTransfer);

        // Get current persisted Tenants payment methods
        $persistedPaymentMethodTransfers = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        // Get current configured payment methods
        $configuredPaymentMethodTransfers = $paymentMethodCollectionResponseTransfer->getPaymentMethods()->getArrayCopy();

        $paymentMethodTransfersToAdd = $this->getPaymentMethodsToAdd($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);

        foreach ($paymentMethodTransfersToAdd as $paymentMethodTransferToAdd) {
            // Add the passed configuration to the default configuration.
            $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($paymentMethodTransferToAdd->getPaymentMethodAppConfiguration()->getCheckoutConfiguration());

            $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
            $addPaymentMethodTransfer
                ->setName($paymentMethodTransferToAdd->getName())
                ->setProviderName($paymentMethodTransferToAdd->getProviderName())
                ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
                ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

            $this->appPaymentRepository->savePaymentMethod($paymentMethodTransferToAdd, $tenantIdentifier);

            $this->paymentMethodMessageSender->sendAddPaymentMethodMessage($addPaymentMethodTransfer, $appConfigTransfer);
        }

        return $appConfigTransfer;
    }

    protected function getPaymentMethodsToAdd(array $persistedPaymentMethodTransfers, array $configuredPaymentMethodTransfers): array
    {
        $methodsToAdd = [];

        foreach ($configuredPaymentMethodTransfers as $configuredPaymentMethodTransfer) {
            if (!$this->isPaymentMethodPersisted($configuredPaymentMethodTransfer->getName(), $persistedPaymentMethodTransfers)) {
                $methodsToAdd[] = $configuredPaymentMethodTransfer;
            }
        }

        return $methodsToAdd;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     */
    protected function isPaymentMethodPersisted(string $paymentMethodName, array $persistedPaymentMethodTransfers): bool
    {
        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            if ($paymentMethodName === $persistedPaymentMethodTransfer->getName()) {
                return true;
            }
        }

        return false;
    }

    protected function getPaymentMethodsToDelete(array $persistedPaymentMethodTransfers, array $configuredPaymentMethodTransfers): array
    {
        $methodsToDelete = [];

        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            if (!$this->isPaymentMethodConfigured($persistedPaymentMethodTransfer->getName(), $configuredPaymentMethodTransfers)) {
                $methodsToDelete[] = $persistedPaymentMethodTransfer;
            }
        }

        return $methodsToDelete;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers
     */
    protected function isPaymentMethodConfigured(string $paymentMethodName, array $configuredPaymentMethodTransfers): bool
    {
        foreach ($configuredPaymentMethodTransfers as $configuredPaymentMethodTransfer) {
            if ($paymentMethodName === $configuredPaymentMethodTransfer->getName()) {
                return true;
            }
        }

        return false;
    }

    public function deletePaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        if (!($this->appPaymentPlatformPlugin instanceof AppPaymentPaymentMethodsPlatformPluginInterface)) {
            $deletePaymentMethodTransfer = new DeletePaymentMethodTransfer();
            $deletePaymentMethodTransfer
                ->setName($this->appPaymentConfig->getPaymentMethodName())
                ->setProviderName($this->appPaymentConfig->getPaymentProviderName());

            $this->paymentMethodMessageSender->sendDeletePaymentMethodMessage($deletePaymentMethodTransfer, $appConfigTransfer);

            return $appConfigTransfer;
        }

        $tenantIdentifier = $appConfigTransfer->getTenantIdentifierOrFail();

        $paymentMethodConfigurationRequestTransfer = new PaymentMethodConfigurationRequestTransfer();
        $paymentMethodConfigurationRequestTransfer->setAppConfig($appConfigTransfer);

        $paymentMethodCollectionResponseTransfer = $this->appPaymentPlatformPlugin->configurePaymentMethods($paymentMethodConfigurationRequestTransfer);

        // Get current persisted Tenants payment methods
        $persistedPaymentMethodTransfers = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        // Get current configured payment methods
        $configuredPaymentMethodTransfers = $paymentMethodCollectionResponseTransfer->getPaymentMethods()->getArrayCopy();

        $paymentMethodTransfersToDelete = $this->getPaymentMethodsToDelete($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);

        foreach ($paymentMethodTransfersToDelete as $paymentMethodTransferToDelete) {
            $deletePaymentMethodTransfer = new DeletePaymentMethodTransfer();
            $deletePaymentMethodTransfer
                ->setName($paymentMethodTransferToDelete->getName())
                ->setProviderName($paymentMethodTransferToDelete->getProviderName());

            $this->appPaymentRepository->deletePaymentMethod($paymentMethodTransferToDelete, $tenantIdentifier);

            $this->paymentMethodMessageSender->sendDeletePaymentMethodMessage($deletePaymentMethodTransfer, $appConfigTransfer);
        }

        return $appConfigTransfer;
    }

    protected function getDefaultPaymentMethodAppConfiguration(): PaymentMethodAppConfigurationTransfer
    {
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

        return $paymentMethodAppConfigurationTransfer;
    }
}
