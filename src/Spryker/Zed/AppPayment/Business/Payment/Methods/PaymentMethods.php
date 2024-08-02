<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Methods;

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

class PaymentMethods
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentConfig $appPaymentConfig,
        protected PaymentMethodMessageSender $paymentMethodMessageSender
    ) {
    }

    public function addPaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        // Do not send the message(s) when App is in state "disconnected" or when the app is marked as inactive.
        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED || $appConfigTransfer->getIsActive() === false) {
            return $appConfigTransfer;
        }

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

        /** @var \Generated\Shared\Transfer\PaymentMethodTransfer $paymentMethodTransfer */
        foreach ($paymentMethodCollectionResponseTransfer->getPaymentMethodsToAdd() as $paymentMethodTransfer) {
            // Add the passed configuration to the default configuration.
            $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($paymentMethodTransfer->getPaymentMethodAppConfiguration()->getCheckoutConfiguration());

            $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
            $addPaymentMethodTransfer
                ->setName($paymentMethodTransfer->getName())
                ->setProviderName($paymentMethodTransfer->getProviderName())
                ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
                ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

            $this->paymentMethodMessageSender->sendAddPaymentMethodMessage($addPaymentMethodTransfer, $appConfigTransfer);
        }

        return $appConfigTransfer;
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

        $paymentMethodConfigurationRequestTransfer = new PaymentMethodConfigurationRequestTransfer();
        $paymentMethodConfigurationRequestTransfer->setAppConfig($appConfigTransfer);

        $paymentMethodCollectionResponseTransfer = $this->appPaymentPlatformPlugin->configurePaymentMethods($paymentMethodConfigurationRequestTransfer);

        foreach ($paymentMethodCollectionResponseTransfer->getPaymentMethodsToDelete() as $paymentMethodTransfer) {
            $deletePaymentMethodTransfer = new DeletePaymentMethodTransfer();
            $deletePaymentMethodTransfer
                ->setName($paymentMethodTransfer->getName())
                ->setProviderName($paymentMethodTransfer->getProviderName());

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
