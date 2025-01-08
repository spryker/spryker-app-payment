<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\RestApi;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformConfirmPreOrderPluginInterface;
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
 * @group ConfirmPreOrderPaymentApiTest
 * Add your own group annotations below this line
 */
class ConfirmPreOrderPaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentCanBeUpdatedWithTheMissingOrderReference(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setStatus(PaymentStatus::STATUS_CAPTURED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200NadPersistsPaymentStatusHistory(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setStatus(PaymentStatus::STATUS_AUTHORIZED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertPaymentStatusHistory(PaymentStatus::STATUS_AUTHORIZED, $transactionId);
    }

    /**
     * Covers the case when the transactionId is a temporary one, and needs to be updated in the spy_payment table.
     */
    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenTheConfirmPreOrderPaymentImplementationReturnsANewTransactionIdAndThePaymentIsUpdatedWithTheNewTransactionId(): void
    {
        // Arrange
        $preOrderPaymentTransactionId = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($preOrderPaymentTransactionId, $tenantIdentifier);

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $preOrderPaymentTransactionId,
            ConfirmPreOrderPaymentRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            ConfirmPreOrderPaymentRequestTransfer::PAYMENT => $paymentTransfer,
        ]);

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setStatus(PaymentStatus::STATUS_CAPTURED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer, $transactionId) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                // The implementation changes the transactionID. This happens when a Payment is created with a temporary transactionId.
                $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPayment();
                $paymentTransfer->setTransactionId($transactionId);

                $confirmPreOrderPaymentResponseTransfer->setPayment($paymentTransfer);

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        // Test that the transactionId was updated, and we can find a payment with the new transactionId.
        $this->tester->seePaymentWithTransactionId($transactionId);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentPlatformHasNotImplementedTheConfirmPreOrderPaymentPlatformPlugin(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200AndProcessesUnprocessedWebhooks(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $webhookRequestTransfer = new WebhookRequestTransfer();
        $webhookRequestTransfer->setIdentifier($transactionId);

        $this->tester->haveWebhookRequestPersisted($webhookRequestTransfer);

        $this->tester->assertWebhookIsPersisted($transactionId);

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // This will mark the persisted webhook as being handled.
        $webhookHandlerPlugin = $this->tester->createSuccessfulWebhookHandlerPlugin();
        $this->tester->setDependency(AppWebhookDependencyProvider::PLUGINS_WEBHOOK_HANDLER, [$webhookHandlerPlugin]);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $this->tester->assertWebhookIsNotPersisted($transactionId);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentCanNotBeUpdatedWithTheMissingOrderReference(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(false)
            ->setMessage('Payment confirmation failed')
            ->setStatus(PaymentStatus::STATUS_CAPTURED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentPlatformImplementationThrowsAnException(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer): void {
                // Ensure that the AppConfig is always passed to the platform plugin.
                throw new Exception('Confirm pre-order payment failed');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }
}
