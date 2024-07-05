<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\CancelPaymentRequestBuilder;
use Generated\Shared\DataBuilder\CapturePaymentRequestBuilder;
use Generated\Shared\DataBuilder\InitializePaymentRequestBuilder;
use Generated\Shared\DataBuilder\PaymentBuilder;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManager;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepository;
use SprykerTest\Shared\AppKernel\Helper\AppConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;

class AppPaymentHelper extends Module
{
    use DataCleanupHelperTrait;
    use AppConfigHelperTrait;

    public function assertPaymentIsInState(string $transactionId, string $expectedState): void
    {
        $paymentEntity = SpyPaymentQuery::create()->findOneByTransactionId($transactionId);

        $this->assertNotNull($paymentEntity, sprintf('Could not find a payment with transaction id "%s".', $transactionId));
        $this->assertSame($expectedState, $paymentEntity->getStatus(), sprintf('Expected payment to be in status "%s" but got "%s"', $expectedState, $paymentEntity->getStatus()));
    }

    public function havePaymentForTransactionId(
        string $transactionId,
        string $tenantIdentifier,
        string $status = PaymentStatus::STATUS_NEW
    ): PaymentTransfer {
        $seed = [
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::STATUS => $status,
        ];

        return $this->havePayment($seed);
    }

    public function havePayment(array $seed = []): PaymentTransfer
    {
        $quoteBuilder = new QuoteBuilder($seed);
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $paymentTransfer = (new PaymentBuilder($seed))->build();
        $quoteTransfer = $quoteBuilder->build();
        $orderReference = $quoteTransfer->getOrderReference();

        $paymentTransfer
            ->setQuote($quoteTransfer)
            ->setOrderReference($orderReference)
            ->setStatus($seed['status'] ?? PaymentStatus::STATUS_NEW);

        $paymentEntityManager = new AppPaymentEntityManager();

        $this->getDataCleanupHelper()->addCleanup(function () use ($orderReference): void {
            $paymentEntity = SpyPaymentQuery::create()->findOneByOrderReference($orderReference);

            if ($paymentEntity) {
                $paymentEntity->delete();
            }
        });

        return $paymentEntityManager->createPayment($paymentTransfer);
    }

    public function haveInitializePaymentRequestTransfer(array $seed = []): InitializePaymentRequestTransfer
    {
        $tenantIdentifier = $seed[InitializePaymentRequestTransfer::TENANT_IDENTIFIER] ?? Uuid::uuid4()->toString();
        $quoteBuilder = new QuoteBuilder();
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $initializePaymentRequestTransfer = (new InitializePaymentRequestBuilder($seed))->build();
        $initializePaymentRequestTransfer->setOrderData($quoteBuilder->build());
        $initializePaymentRequestTransfer->setTenantIdentifier($tenantIdentifier);

        $this->getDataCleanupHelper()->addCleanup(function () use ($tenantIdentifier): void {
            $paymentEntity = SpyPaymentQuery::create()->findOneByTenantIdentifier($tenantIdentifier);

            if ($paymentEntity) {
                $paymentEntity->delete();
            }
        });

        return $initializePaymentRequestTransfer;
    }

    /**
     * This method should only be used by the PlatformPluginInterface implementation tests.
     * It Provides a request transfer as it would come from the Payment module.
     */
    public function haveInitializePaymentRequestWithAppConfigTransfer(array $seed = []): InitializePaymentRequestTransfer
    {
        $tenantIdentifier = $seed[InitializePaymentRequestTransfer::TENANT_IDENTIFIER] ?? Uuid::uuid4()->toString();
        $quoteBuilder = new QuoteBuilder($seed);
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $initializePaymentRequestTransfer = (new InitializePaymentRequestBuilder($seed))->build();
        $initializePaymentRequestTransfer->setOrderData($quoteBuilder->build());
        $initializePaymentRequestTransfer->setTenantIdentifier($tenantIdentifier);

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);

        $initializePaymentRequestTransfer->setAppConfig($appConfigTransfer);

        return $initializePaymentRequestTransfer;
    }

    public function havePaymentPageRequestTransfer(array $seed = []): PaymentPageRequestTransfer
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $paymentPageRequestTransfer = new PaymentPageRequestTransfer();
        $paymentPageRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $paymentPageRequestTransfer;
    }

    public function havePaymentStatusRequestTransfer(array $seed = []): PaymentStatusRequestTransfer
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $paymentStatusRequestTransfer = new PaymentStatusRequestTransfer();
        $paymentStatusRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $paymentStatusRequestTransfer;
    }

    public function haveCapturePaymentRequestTransfer(array $seed = [], $status = PaymentStatus::STATUS_NEW): CapturePaymentRequestTransfer
    {
        $tenantIdentifier = $seed['tenantIdentifier'] ?? Uuid::uuid4()->toString();
        $transactionId = $seed['transactionId'] ?? Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier, $status);

        $capturePaymentRequestTransfer = (new CapturePaymentRequestBuilder($seed))->build();
        $capturePaymentRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $capturePaymentRequestTransfer;
    }

    public function haveCancelPaymentRequestTransfer(array $seed = [], $status = PaymentStatus::STATUS_NEW): CancelPaymentRequestTransfer
    {
        $tenantIdentifier = $seed['tenantIdentifier'] ?? Uuid::uuid4()->toString();
        $transactionId = $seed['transactionId'] ?? Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier, $status);

        $cancelPaymentRequestTransfer = (new CancelPaymentRequestBuilder($seed))->build();
        $cancelPaymentRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $cancelPaymentRequestTransfer;
    }

    public function getPaymentTransferByTransactionId(string $transactionId): PaymentTransfer
    {
        return (new AppPaymentRepository())->getPaymentByTransactionId($transactionId);
    }

    public function dontSeePaymentByTenantIdentifier(string $tenantIdentifier): void
    {
        $paymentQuery = SpyPaymentQuery::create();
        $paymentQuery->filterByTenantIdentifier($tenantIdentifier);

        $this->assertEmpty($paymentQuery->find(), 'Found payment by tenant identifier');
    }

    public function seePaymentByTenantIdentifier(string $tenantIdentifier): void
    {
        $paymentQuery = SpyPaymentQuery::create();
        $paymentQuery->filterByTenantIdentifier($tenantIdentifier);

        $this->assertGreaterThan(0, $paymentQuery->find()->count(), 'Did not find payment by tenant identifier');
    }
}
