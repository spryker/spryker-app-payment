<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\Helper;

use Codeception\Module;
use Codeception\Stub;
use Exception;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Glue\AppWebhookBackendApi\AppWebhookBackendApiDependencyProvider;
use Spryker\Glue\AppWebhookBackendApi\Plugin\AppWebhookBackendApi\GlueRequestWebhookMapperPluginInterface;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

class WebhookApiHelper extends Module
{
    use DependencyHelperTrait;

    public function mockGlueRequestWebhookMapperPlugin(
        string $webhookDataType,
        ?string $transactionId = null,
        ?string $refundId = null
    ): void {
        $glueRequestWebhookMapperPluginMock = Stub::makeEmpty(GlueRequestWebhookMapperPluginInterface::class, [
            'mapGlueRequestDataToWebhookRequestTransfer' => function (GlueRequestTransfer $glueRequestTransfer, WebhookRequestTransfer $webhookRequestTransfer) use ($webhookDataType, $transactionId, $refundId): WebhookRequestTransfer {
                $webhookRequestTransfer->setType($webhookDataType);

                if ($transactionId) {
                    $webhookRequestTransfer->setTransactionId($transactionId);
                }

                if ($refundId) {
                    $webhookRequestTransfer->setRefund((new PaymentRefundTransfer())->setRefundId($refundId));
                }

                return $webhookRequestTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(
            AppWebhookBackendApiDependencyProvider::PLUGIN_GLUE_REQUEST_WEBHOOK_MAPPER,
            $glueRequestWebhookMapperPluginMock,
        );
    }

    public function mockPaymentPlatform(
        bool $webhookResponseSuccessful,
        ?string $paymentStatus = null,
        ?string $paymentRefundStatus = null
    ): void {
        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer, WebhookResponseTransfer $webhookResponseTransfer) use ($webhookResponseSuccessful, $paymentStatus, $paymentRefundStatus): WebhookResponseTransfer {
                $webhookResponseTransfer->setIsSuccessful($webhookResponseSuccessful);

                if ($paymentStatus) {
                    $webhookResponseTransfer->setPaymentStatus($paymentStatus);
                }

                if ($paymentRefundStatus) {
                    $webhookResponseTransfer->setRefundStatus($paymentRefundStatus);
                }

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $webhookRequestTransfer->getAppConfig());

                // Ensure that the PaymentTransfer is always passed to the platform plugin.
                $this->assertInstanceOf(PaymentTransfer::class, $webhookRequestTransfer->getPayment());

                return $webhookResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
    }

    public function mockPaymentPlatformThatThrowsAnException(): void
    {
        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'handleWebhook' => function (): WebhookResponseTransfer {
                throw new Exception('PaymentPlatformPluginInterface::handleWebhook() exception.');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
    }
}
