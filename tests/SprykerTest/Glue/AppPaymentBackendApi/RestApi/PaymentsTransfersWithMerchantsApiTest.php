<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\RestApi;

use ArrayObject;
use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransferQuery;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppMerchant\Communication\Plugin\AppPayment\MerchantsPaymentsTransmissionsRequestExtenderPlugin;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use SprykerTest\Glue\AppPaymentBackendApi\AppPaymentBackendApiTester;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * This test belongs to the project code. Merchants are not part of the PaymentBackendApi module but will be triggered through it.
 * We can not add this tests to the AppMerchantPaymentModule because it offers only a plugin to mutate the PaymentsTransmissionsRequestTransfer.
 *
 * Do not merge the two doc-blocks above and below this comment. The CS Fixer will remove the content from the above.
 */

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group AppPaymentBackendApi
 * @group RestApi
 * @group PaymentsTransfersWithMerchantsApiTest
 * Add your own group annotations below this line
 */
class PaymentsTransfersWithMerchantsApiTest extends Unit
{
    use DataCleanupHelperTrait;
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testGivenTwoOrdersEachWithThreeItemsWhereOneOfThemIsFromAMerchantWhenTheExtenderRunsThenPaymentTransmissionsAreAddedTwoItemsAndTwoWithMerchantsAndEachHasTwoItemsAndOrderItemsWithoutMerchantsAreIgnored(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $transactionId1 = Uuid::uuid4()->toString();
        $transactionId2 = Uuid::uuid4()->toString();

        $orderReference1 = Uuid::uuid4()->toString();
        $orderReference2 = Uuid::uuid4()->toString();

        $transferId1 = Uuid::uuid4()->toString();
        $transferId2 = Uuid::uuid4()->toString();

        $merchantTransfer1 = $this->tester->haveMerchantPersisted([MerchantTransfer::TENANT_IDENTIFIER => $tenantIdentifier]);
        $merchantTransfer2 = $this->tester->haveMerchantPersisted([MerchantTransfer::TENANT_IDENTIFIER => $tenantIdentifier]);

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        // First Payment
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId1,
            PaymentTransfer::ORDER_REFERENCE => $orderReference1,
        ]);

        // Second Payment
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId2,
            PaymentTransfer::ORDER_REFERENCE => $orderReference2,
        ]);

        /** @var array<\Generated\Shared\Transfer\OrderItemTransfer> $orderItems */
        $orderItems = [
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::AMOUNT => 180]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer1->getMerchantReference(), OrderItemTransfer::AMOUNT => 45]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference2, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer2->getMerchantReference(), OrderItemTransfer::AMOUNT => 90]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::AMOUNT => 180]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer1->getMerchantReference(), OrderItemTransfer::AMOUNT => 45]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference2, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer2->getMerchantReference(), OrderItemTransfer::AMOUNT => 90]),
        ];

        $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $paymentTransmissionTransfer->setOrderItems(new ArrayObject($orderItems));

        $paymentsTransmissionsRequestTransfer = new PaymentsTransmissionsRequestTransfer();
        $paymentsTransmissionsRequestTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->addPaymentTransmission($paymentTransmissionTransfer);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) use ($transferId1, $transferId2) {
                $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                $this->assertCount(2, $paymentsTransmissionsRequestTransfer->getPaymentsTransmissions());

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                /** @var \ArrayObject<int, \Generated\Shared\Transfer\PaymentTransmissionTransfer> $paymentsTransmissionsTransferCollection */
                $paymentsTransmissionsTransferCollection = $paymentsTransmissionsRequestTransfer->getPaymentsTransmissions();
                $paymentsTransmissionsTransferCollection[0]->setTransferId($transferId1)->setIsSuccessful(true);
                $paymentsTransmissionsTransferCollection[1]->setTransferId($transferId2)->setIsSuccessful(true);

                $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmissionsTransferCollection[0]);
                $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmissionsTransferCollection[1]);

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, [
            new MerchantsPaymentsTransmissionsRequestExtenderPlugin(),
        ]);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('Content-Type', 'application/json');

        $orderItemsRequestData = array_map(function (OrderItemTransfer $orderItem): array {
            return $orderItem->modifiedToArray();
        }, $orderItems);

        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $orderItemsRequestData],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        // Assert
        // PaymentTransmission 1 - OrderItems with Merchant 1
        $expectedPaymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $expectedPaymentTransmissionTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setTransactionId($transactionId1)
            ->setTransferId($transferId1)
            ->setOrderReference($orderReference1)
            ->setItemReferences([$orderItems[1]->getItemReferenceOrFail(), $orderItems[4]->getItemReferenceOrFail()])
            ->setAmount('90');

        $this->tester->assertPaymentTransferEqualsPaymentTransmission($transferId1, $expectedPaymentTransmissionTransfer, $merchantTransfer1->getMerchantReference());

        // PaymentTransmission 3 - OrderItems with Merchant 2
        $expectedPaymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $expectedPaymentTransmissionTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setTransactionId($transactionId2)
            ->setTransferId($transferId2)
            ->setOrderReference($orderReference2)
            ->setItemReferences([$orderItems[2]->getItemReferenceOrFail(), $orderItems[5]->getItemReferenceOrFail()])
            ->setAmount('180');

        $this->tester->assertPaymentTransferEqualsPaymentTransmission($transferId2, $expectedPaymentTransmissionTransfer, $merchantTransfer2->getMerchantReference());

        $this->getDataCleanupHelper()->_addCleanup(function () use ($tenantIdentifier): void {
            SpyPaymentQuery::create()->filterByTenantIdentifier($tenantIdentifier)->delete();
            SpyPaymentTransferQuery::create()->filterByTenantIdentifier($tenantIdentifier)->delete();
        });
    }

    /**
     * Ensure that even failed payment transmissions are returned in the response so the failure can be explored on the Tenant side with a message indicating what has happened.
     */
    public function testGivenOrderItemsWhichWouldTriggerAPayoutWhenThePaymentTransfersFailOnThePlatformImplementationThenTheResponseContainsTheFailedPaymentTransmissionsWithMessages(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $transactionId1 = Uuid::uuid4()->toString();
        $transactionId2 = Uuid::uuid4()->toString();

        $orderReference1 = Uuid::uuid4()->toString();
        $orderReference2 = Uuid::uuid4()->toString();

        $merchantTransfer1 = $this->tester->haveMerchantPersisted([MerchantTransfer::TENANT_IDENTIFIER => $tenantIdentifier]);
        $merchantTransfer2 = $this->tester->haveMerchantPersisted([MerchantTransfer::TENANT_IDENTIFIER => $tenantIdentifier]);

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        // First Payment
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId1,
            PaymentTransfer::ORDER_REFERENCE => $orderReference1,
        ]);

        // Second Payment
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId2,
            PaymentTransfer::ORDER_REFERENCE => $orderReference2,
        ]);

        /** @var array<\Generated\Shared\Transfer\OrderItemTransfer> $orderItems */
        $orderItems = [
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::AMOUNT => 180]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer1->getMerchantReference(), OrderItemTransfer::AMOUNT => 45]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference2, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer2->getMerchantReference(), OrderItemTransfer::AMOUNT => 90]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::AMOUNT => 180]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference1, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer1->getMerchantReference(), OrderItemTransfer::AMOUNT => 45]),
            $this->tester->haveOrderItem([OrderItemTransfer::ORDER_REFERENCE => $orderReference2, OrderItemTransfer::MERCHANT_REFERENCE => $merchantTransfer2->getMerchantReference(), OrderItemTransfer::AMOUNT => 90]),
        ];

        $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $paymentTransmissionTransfer->setOrderItems(new ArrayObject($orderItems));

        $paymentsTransmissionsRequestTransfer = new PaymentsTransmissionsRequestTransfer();
        $paymentsTransmissionsRequestTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->addPaymentTransmission($paymentTransmissionTransfer);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) {
                $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                $this->assertCount(2, $paymentsTransmissionsRequestTransfer->getPaymentsTransmissions());

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                // Mark both transfers as failed
                /** @var \ArrayObject<int, \Generated\Shared\Transfer\PaymentTransmissionTransfer> $paymentsTransmissionsTransferCollection */
                $paymentsTransmissionsTransferCollection = $paymentsTransmissionsRequestTransfer->getPaymentsTransmissions();
                $paymentsTransmissionsTransferCollection[0]->setIsSuccessful(false)->setMessage('Transfer failed');
                $paymentsTransmissionsTransferCollection[1]->setIsSuccessful(false)->setMessage('Transfer failed');

                $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmissionsTransferCollection[0]);
                $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmissionsTransferCollection[1]);

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, [
            new MerchantsPaymentsTransmissionsRequestExtenderPlugin(),
        ]);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('Content-Type', 'application/json');

        $orderItemsRequestData = array_map(function (OrderItemTransfer $orderItem): array {
            return $orderItem->modifiedToArray();
        }, $orderItems);

        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $orderItemsRequestData],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $this->tester->assertPaymentTransferIsNotPersisted($tenantIdentifier, $merchantTransfer1->getMerchantReference());
        $this->tester->assertPaymentTransferIsNotPersisted($tenantIdentifier, $merchantTransfer2->getMerchantReference());
    }
}
