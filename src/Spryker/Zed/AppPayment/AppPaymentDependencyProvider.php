<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\AppConfigValidateResponseTransfer;
use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusResponseTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeBridge;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeBridge;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPaymentPagePluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Service\AppPaymentToUtilEncodingServiceBridge;
use Spryker\Zed\AppPayment\Dependency\Service\AppPaymentToUtilEncodingServiceInterface;
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
    public const PLUGIN_PLATFORM = 'PAYMENT:PLUGIN_PLATFORM';

    /**
     * @var string
     */
    public const FACADE_APP_KERNEL = 'PAYMENT:FACADE_APP_KERNEL';

    /**
     * @var string
     */
    public const FACADE_MESSAGE_BROKER = 'PAYMENT:FACADE_MESSAGE_BROKER';

    /**
     * @var string
     */
    public const SERVICE_UTIL_ENCODING = 'PAYMENT:SERVICE_UTIL_ENCODING';

    /**
     * @var string
     */
    public const PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER = 'PAYMENT:PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addPlatformPlugin($container);
        $container = $this->addAppKernelFacade($container);
        $container = $this->addMessageBrokerFacade($container);
        $container = $this->addUtilEncodingService($container);

        return $this->addPaymentsTransmissionRequestExtenderPlugins($container);
    }

    protected function addPlatformPlugin(Container $container): Container
    {
        $container->set(static::PLUGIN_PLATFORM, function (): PlatformPluginInterface {
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
    protected function getPlatformPlugin(): PlatformPluginInterface
    {
        // @codeCoverageIgnoreStart
        return new class implements PlatformPluginInterface, PlatformPaymentPagePluginInterface {
            public function validateConfiguration(AppConfigTransfer $appConfigTransfer): AppConfigValidateResponseTransfer
            {
                return (new AppConfigValidateResponseTransfer())->setIsSuccessful(true);
            }

            public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
            {
                return (new InitializePaymentResponseTransfer())->setIsSuccessful(true);
            }

            public function handleWebhook(
                WebhookRequestTransfer $webhookRequestTransfer,
                WebhookResponseTransfer $webhookResponseTransfer
            ): WebhookResponseTransfer {
                return (new WebhookResponseTransfer())->setIsSuccessful(true);
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

            public function getPaymentStatus(PaymentStatusRequestTransfer $paymentStatusRequestTransfer): PaymentStatusResponseTransfer
            {
                return (new PaymentStatusResponseTransfer())->setIsSuccessful(true);
            }

            public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
            {
                return (new PaymentPageResponseTransfer())->setIsSuccessful(true);
            }

            public function transferPayments(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsResponseTransfer
            {
                return (new PaymentsTransmissionsResponseTransfer())->setIsSuccessful(true);
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

    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, static function (Container $container): AppPaymentToUtilEncodingServiceInterface {
            return new AppPaymentToUtilEncodingServiceBridge($container->getLocator()->utilEncoding()->service());
        });

        return $container;
    }

    protected function addPaymentsTransmissionRequestExtenderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, function (): array {
            return $this->getPaymentsTransmissionRequestExtenderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentsTransmissionsRequestExtenderPluginInterface>
     */
    protected function getPaymentsTransmissionRequestExtenderPlugins(): array
    {
        return [];
    }
}
