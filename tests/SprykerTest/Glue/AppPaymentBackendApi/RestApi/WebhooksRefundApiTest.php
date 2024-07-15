<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\RestApi;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use Spryker\Zed\AppPayment\Communication\Plugin\AppWebhook\PaymentWebhookHandlerPlugin;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppWebhook\AppWebhookDependencyProvider;
use SprykerTest\Glue\AppPaymentBackendApi\AppPaymentBackendApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group AppPaymentBackendApi
 * @group RestApi
 * @group WebhooksRefundApiTest
 * Add your own group annotations below this line
 */
class WebhooksRefundApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    protected function _setUp(): void
    {
        parent::_setUp();

        // Ensure we only test against "our" known Plugin.
        $this->getDependencyHelper()->setDependency(AppWebhookDependencyProvider::PLUGINS_WEBHOOK_HANDLER, [
            new PaymentWebhookHandlerPlugin(),
        ]);
    }

    /**
     * @dataProvider refundStatusesDataProvider
     *
     * @param \Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus $oldPaymentRefundStatus
     * @param \Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus $newPaymentRefundStatus
     *
     * @return void
     */
    public function testGivenPaymentRefundInAnyStateWhenThePlatformPluginReturnsSuccessfulWebhookResponseAndAnyPaymentRefundStatusThenPaymentRefundStatusIsChanged(
        string $oldPaymentRefundStatus,
        string $newPaymentRefundStatus
    ): void {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);
        $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, $oldPaymentRefundStatus);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::REFUND, $transactionId, $refundId);
        $this->tester->mockPaymentPlatform(true, null, $newPaymentRefundStatus);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['web hook content received from third party payment provider']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentRefundIsInStatus($refundId, $newPaymentRefundStatus);
    }

    public function testHandleRefundWebhookReturnsA400BadRequestWhenThePlatformPluginReturnsFailedWebhookResponseThenPaymentRefundStatusIsNotChanged(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);
        $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, PaymentRefundStatus::SUCCEEDED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::REFUND, $transactionId, $refundId);
        $this->tester->mockPaymentPlatform(false, null, PaymentRefundStatus::FAILED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['web hook content received from third party payment provider']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testHandleRefundWebhookReturnsA400BadRequestWhenHandlingWebhookThroughThePaymentPlatformPluginThrowsAnException(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);
        $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, PaymentRefundStatus::SUCCEEDED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::REFUND, $transactionId, $refundId);
        $this->tester->mockPaymentPlatformThatThrowsAnException();

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['web hook content received from third party payment provider']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('PaymentPlatformPluginInterface::handleWebhook() exception.');
    }

    public function testHandleRefundWebhookReturnsA400BadRequestWhenThereIsNoSuchPaymentRefund(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::REFUND, $transactionId, $refundId);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['web hook content received from third party payment provider']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::refundByRefundIdNotFound($refundId));
    }

    public function testHandleRefundWebhookReturnsA400BadRequestWhenRefundIdHasBeenChangedByPlatformPlugin(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);
        $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, PaymentRefundStatus::FAILED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::REFUND, $transactionId, $refundId);
        $this->mockPaymentPlatformWithInvalidRefundIdChange($transactionId, true, null, PaymentRefundStatus::SUCCEEDED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['web hook content received from third party payment provider']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::refundByRefundIdNotFound('invalid refund id'));
    }

    public function refundStatusesDataProvider(): array
    {
        $refundStatuses = [
            PaymentRefundStatus::PENDING,
            PaymentRefundStatus::SUCCEEDED,
            PaymentRefundStatus::FAILED,
            PaymentRefundStatus::CANCELED,
            PaymentRefundStatus::PROCESSING,
        ];
        $refundStatusesData = [];

        foreach ($refundStatuses as $refundStatusOld) {
            foreach ($refundStatuses as $refundStatusNew) {
                $refundStatusesData[] = [$refundStatusOld, $refundStatusNew];
            }
        }

        return $refundStatusesData;
    }

    public function mockPaymentPlatformWithInvalidRefundIdChange(
        string $transactionId,
        bool $webhookResponseSuccessful,
        ?string $paymentStatus = null,
        ?string $paymentRefundStatus = null
    ): void {
        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer) use ($transactionId, $webhookResponseSuccessful, $paymentStatus, $paymentRefundStatus): WebhookResponseTransfer {
                $webhookResponseTransfer = new WebhookResponseTransfer();
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

                $webhookRequestTransfer->getRefund()->setRefundId('invalid refund id');

                return $webhookResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
    }
}
