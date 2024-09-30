<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment;

use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationRequestTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeBridge;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppWebhookFacadeBridge;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppWebhookFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeBridge;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformMarketplacePluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentPagePluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 */
class AppPaymentDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const PLUGIN_PLATFORM = 'APP_PAYMENT:PLUGIN_PLATFORM';

    /**
     * @var string
     */
    public const FACADE_APP_KERNEL = 'APP_PAYMENT:FACADE_APP_KERNEL';

    /**
     * @var string
     */
    public const FACADE_MESSAGE_BROKER = 'APP_PAYMENT:FACADE_MESSAGE_BROKER';

    /**
     * @var string
     */
    public const FACADE_APP_WEBHOOK = 'APP_PAYMENT:FACADE_APP_WEBHOOK';

    /**
     * @var string
     */
    public const SERVICE_UTIL_ENCODING = 'APP_PAYMENT:SERVICE_UTIL_ENCODING';

    /**
     * @var string
     */
    public const PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER = 'APP_PAYMENT:PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addPlatformPlugin($container);
        $container = $this->addAppKernelFacade($container);
        $container = $this->addMessageBrokerFacade($container);
        $container = $this->addAppWebhookFacade($container);

        return $this->addPaymentTransmissionRequestExtenderPlugins($container);
    }

    protected function addPlatformPlugin(Container $container): Container
    {
        $container->set(static::PLUGIN_PLATFORM, function (): AppPaymentPlatformPluginInterface {
            // @codeCoverageIgnoreStart
            return $this->getPlatformPlugin();
            // @codeCoverageIgnoreEnd
        });

        return $container;
    }

    /**
     * This method must be overridden in the project implementation of the AppPaymentDependencyProvider.
     * This one exists only for simpler testing.
     */
    protected function getPlatformPlugin(): AppPaymentPlatformPluginInterface
    {
        // @codeCoverageIgnoreStart
        return new class implements AppPaymentPlatformPluginInterface, AppPaymentPlatformPaymentPagePluginInterface, AppPaymentPlatformMarketplacePluginInterface, AppPaymentPlatformPaymentMethodsPluginInterface {
            public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
            {
                return (new InitializePaymentResponseTransfer())->setIsSuccessful(true);
            }

            public function capturePayment(CapturePaymentRequestTransfer $capturePaymentRequestTransfer): CapturePaymentResponseTransfer
            {
                return (new CapturePaymentResponseTransfer())->setIsSuccessful(true);
            }

            public function cancelPayment(CancelPaymentRequestTransfer $cancelPaymentRequestTransfer): CancelPaymentResponseTransfer
            {
                return (new CancelPaymentResponseTransfer())->setIsSuccessful(true);
            }

            public function refundPayment(RefundPaymentRequestTransfer $refundPaymentRequestTransfer): RefundPaymentResponseTransfer
            {
                return (new RefundPaymentResponseTransfer())->setIsSuccessful(true);
            }

            public function handleWebhook(
                WebhookRequestTransfer $webhookRequestTransfer,
                WebhookResponseTransfer $webhookResponseTransfer
            ): WebhookResponseTransfer {
                return (new WebhookResponseTransfer())->setIsSuccessful(true);
            }

            public function getPaymentStatus(PaymentStatusRequestTransfer $paymentStatusRequestTransfer): PaymentStatusResponseTransfer
            {
                return (new PaymentStatusResponseTransfer())->setIsSuccessful(true);
            }

            public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
            {
                return (new PaymentPageResponseTransfer())->setIsSuccessful(true);
            }

            public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer
            {
                return (new PaymentTransmissionsResponseTransfer())->setIsSuccessful(true);
            }

            public function configurePaymentMethods(
                PaymentMethodConfigurationRequestTransfer $paymentMethodConfigurationRequestTransfer
            ): PaymentMethodConfigurationResponseTransfer {
                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setProviderName('test-payment-provider-name')
                    ->setName('test-payment-method-name');

                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            }
        };
        // @codeCoverageIgnoreEnd
    }

    protected function addAppKernelFacade(Container $container): Container
    {
        $container->set(static::FACADE_APP_KERNEL, static function (Container $container): AppPaymentToAppKernelFacadeInterface {
            return new AppPaymentToAppKernelFacadeBridge($container->getLocator()->appKernel()->facade());
        });

        return $container;
    }

    protected function addMessageBrokerFacade(Container $container): Container
    {
        $container->set(static::FACADE_MESSAGE_BROKER, static function (Container $container): AppPaymentToMessageBrokerFacadeInterface {
            return new AppPaymentToMessageBrokerFacadeBridge($container->getLocator()->messageBroker()->facade());
        });

        return $container;
    }

    protected function addAppWebhookFacade(Container $container): Container
    {
        $container->set(static::FACADE_APP_WEBHOOK, static function (Container $container): AppPaymentToAppWebhookFacadeInterface {
            return new AppPaymentToAppWebhookFacadeBridge($container->getLocator()->appWebhook()->facade());
        });

        return $container;
    }

    protected function addPaymentTransmissionRequestExtenderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, function (): array {
            return $this->getPaymentTransmissionRequestExtenderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface>
     */
    protected function getPaymentTransmissionRequestExtenderPlugins(): array
    {
        return [];
    }
}
