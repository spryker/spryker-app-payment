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
use Spryker\Glue\AppWebhookBackendApi\AppWebhookBackendApiDependencyProvider;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
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
 * @group WebhooksPaymentApiTest
 * Add your own group annotations below this line
 */
class WebhooksPaymentApiTest extends Unit
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

    public function testGivenPaymentInStateNewWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndAuthorizedStatusThenPaymentIsMovedToAuthorized(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_AUTHORIZED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_AUTHORIZED);
    }

    public function testGivenPaymentInStateNewWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndIsHandledIsSetToFalseThenWebhookResponseIsEmidiatelyReturned(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, null, null, false);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_NEW);
    }

    public function testGivenPaymentInStateAuthorizedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndCaptureStatusThenPaymentIsMovedToCaptured(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURED);
    }

    public function testGivenPaymentInStateAuthorizedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndCaptureFailedStatusThenPaymentIsMovedToCaptureFailed(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURE_FAILED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURE_FAILED);
    }

    public function testGivenPaymentInStateAuthorizedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndCaptureRequestedStatusThenPaymentIsMovedToCaptureRequested(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURE_REQUESTED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURE_REQUESTED);
    }

    public function testGivenPaymentInStateCaptureRequestedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndCapturedStatusThenPaymentIsMovedToCaptured(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURED);
    }

    public function testGivenPaymentInStateCaptureRequestedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndCaptureFailedStatusThenPaymentIsMovedToCaptureFailed(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURE_FAILED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURE_FAILED);
    }

    public function testGivenPaymentInStateCapturedWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferAndSucceededStatusThenPaymentIsMovedToSucceeded(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_SUCCEEDED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_SUCCEEDED);
    }

    public function testGivenNoContentInPostRequestWhenISendTheRequestThenTheApplicationReturnsAHttpStatus400(): void
    {
        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testHandleWebhookReturnsA200OKWhenTheTransactionIdWasResolvedByTheGlueRequestWebhookMapperAndThePlatformPluginReturnsASuccessfulWebhookResponseTransfer(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->tester->mockPaymentPlatform(true, PaymentStatus::STATUS_AUTHORIZED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testHandleWebhookReturnsA400BadRequestWhenTheTransactionIdCouldNotBeExtractedFromTheWebhookRequest(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT);
        $this->tester->mockPaymentPlatform(true, PaymentStatus::STATUS_AUTHORIZED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['no content that contains a transactionId (data::object::id)']);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::getRequestTransactionIdIsMissingOrEmpty());
    }

    /**
     * Just in case, the platform plugin implementation changes the transactionId to a different value.
     */
    public function testHandleWebhookReturnsA400BadRequestWhenTheTransactionIdCouldNotBeFoundWhenPersisting(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_AUTHORIZED, 'invalid transaction id');

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::paymentByTransactionIdNotFound('invalid transaction id'));
    }

    public function testHandleWebhookReturnsA400BadRequestWhenPaymentTransitionIsNotAllowed(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_NEW);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::paymentStatusTransitionNotAllowed(PaymentStatus::STATUS_AUTHORIZED, PaymentStatus::STATUS_NEW));
    }

    public function testHandleWebhookReturnsA400WhenThePlatformPluginReturnsAFailedWebhookResponseTransfer(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->mockPaymentPlatformPlugin(false);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testHandleWebhookDoesNotTransitionPaymentToNewStateWhenThePlatformPluginReturnsAFailedWebhookResponseTransfer(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->mockPaymentPlatformPlugin(false);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_NEW);
    }

    public function testHandleWebhookDoesNotTransitionPaymentToNewStateWhenThePlatformPluginReturnsASuccessfulWebhookResponseTransferButTheDesiredTransitionCanNotBeDone(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->mockPaymentPlatformPlugin(true, 'unknown transition status');

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_NEW);
    }

    public function testHandleWebhookReturnsA400BadRequestWhenTryingToRetrieveTheTransactionIdWithGlueRequestTransferToWebhookTransferMapperPluginWhenNoPluginIsConfigured(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $this->getDependencyHelper()->setDependency(AppWebhookBackendApiDependencyProvider::PLUGIN_GLUE_REQUEST_WEBHOOK_MAPPER, null);

        $this->tester->mockPaymentPlatform(true, PaymentStatus::STATUS_CAPTURED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    /**
     * It could happen we receive multiple Webhook requests, with the same event for the same payment, which in that case would result in the payment status not changing.
     */
    public function testHandleWebhookReturnsA200WebhookRequestIsSuccessfulAndPaymentStateDoesNotChange(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $this->tester->mockGlueRequestWebhookMapperPlugin(WebhookDataType::PAYMENT, $transactionId);
        $this->mockPaymentPlatformPlugin(true, PaymentStatus::STATUS_CAPTURED);

        // Act
        $this->tester->sendPost($this->tester->buildWebhookUrl(), ['data' => ['object' => ['id' => 123456789]]]);

        // Assert
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_CAPTURED);
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    /**
     * Mock ensures that the Payment module passed the AppConfigTransfer and the PaymentTransfer to the PlatformPlugin.
     */
    protected function mockPaymentPlatformPlugin(
        bool $webhookResponseSuccessful,
        ?string $paymentStatus = null,
        ?string $transactionId = null,
        ?bool $isHandled = null
    ): void {
        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer) use ($webhookResponseSuccessful, $paymentStatus, $transactionId, $isHandled): WebhookResponseTransfer {
                $webhookResponseTransfer = new WebhookResponseTransfer();
                $webhookResponseTransfer->setIsSuccessful($webhookResponseSuccessful);
                $webhookResponseTransfer->setPaymentStatus($paymentStatus);
                $webhookResponseTransfer->setIsHandled($isHandled);

                if ($transactionId) {
                    // Changing the transaction id from the PlatformPlugin is not allowed and will fail when persisting the payment.
                    $webhookRequestTransfer->getPayment()->setTransactionId('invalid transaction id');
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
}
