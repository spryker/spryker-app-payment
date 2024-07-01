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
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
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
 * @group PaymentsTransfersApiTest
 * Add your own group annotations below this line
 */
class PaymentsTransfersApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testGivenPaymentsTransfersPostRequestWhenRequestIsValidThenAHttpResponseCode200IsReturnedAndTransfersArePersisted(): void
    {
        // Arrange
        // Disable expander plugins for this test.
//        $this->getDependencyHelper()->setDependency(PaymentDependencyProvider::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, []);

        $transactionId = Uuid::uuid4()->toString();
        $transferId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();
        $merchantReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->haveMerchantPersisted([
            MerchantTransfer::MERCHANT_REFERENCE => $merchantReference,
            MerchantTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) use ($paymentsTransmissionsResponseTransfer, $transferId) {
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                // Add transferId to each PaymentTransmissionTransfer.
                foreach ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions() as $paymentsTransmission) {
                    $paymentsTransmission->setTransferId($transferId);
                    $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmission);
                }

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $expectedPaymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $expectedPaymentTransmissionTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setMerchantReference($merchantReference)
            ->setTransactionId($transactionId)
            ->setTransferId($transferId)
            ->setOrderReference($orderReference)
            ->setItemReferences([$orderItems[0]->getItemReferenceOrFail(), $orderItems[1]->getItemReferenceOrFail()])
            ->setAmount('180')
            ->setCommission('20');

        $this->assertCount(1, $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions());
        $paymentTransmissionTransfer = $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions()[0];

        $this->tester->assertPaymentTransferEqualsPaymentTransmission($paymentTransmissionTransfer->getTransferIdOrFail(), $expectedPaymentTransmissionTransfer);
    }

    public function testWhenThePlatformPluginThrowsAnExceptionWeExpectAFailedResultWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (): void {
                throw new Exception('There was an error in the PlatformPlugin implementation');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('There was an error in the PlatformPlugin implementation');
    }

    public function testWhenThePlatformPluginReturnsAFailedResponseWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function () {
                return (new PaymentsTransmissionsResponseTransfer())
                    ->setIsSuccessful(false)
                    ->setMessage('There was an error in the PlatformPlugin implementation');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('There was an error in the PlatformPlugin implementation');
    }

    public function testWhenThereIsNoPaymentFoundForTheTenantTheAFailedResponseIsReturnedWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => 'not known tenant identifier',
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
                OrderItemTransfer::COMMISSION => 10,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::paymentByTenantIdentifierAndOrderReferenceNotFound($tenantIdentifier, $orderReference));
    }
}
