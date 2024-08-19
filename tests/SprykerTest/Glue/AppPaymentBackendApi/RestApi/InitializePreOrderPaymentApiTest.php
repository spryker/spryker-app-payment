<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\RestApi;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
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
 * @group InitializePreOrderPaymentApiTest
 * Add your own group annotations below this line
 */
class InitializePreOrderPaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testGivenPreOrderPaymentWasAlreadyDoneWhenInitializePaymentPostRequestIsMadeAgainThenThePaymentIsLoadedAndPassedToThePlatformImplementationAndTheOriginalPaymentProviderDataIsReturnedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $paymentProviderDatum = Uuid::uuid4()->toString();

        $this->tester->havePayment([
            PaymentTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $orderTransfer = $initializePaymentRequestTransfer->getOrderData();
        $orderTransfer->setOrderReference(null);

        $initializePaymentRequestTransfer->setOrderData($orderTransfer);

        $initializePaymentRequestTransfer->setPaymentProviderData([
            PaymentTransfer::TRANSACTION_ID => $transactionId, // required field to always be set in pre-order payments.
            'foo' => $paymentProviderDatum,
        ]);

        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setPaymentProviderData([
                PaymentTransfer::TRANSACTION_ID => $transactionId, // required field to always be set in pre-order payments.
                'foo' => $paymentProviderDatum,
            ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                // Ensure the Payment is loaded from the DB and passed to the platform plugin.
                $this->assertInstanceOf(PaymentTransfer::class, $initializePaymentRequestTransfer->getPayment());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonContainsPayment($response);
        $this->tester->seeResponseJsonContainsPaymentProviderData($response, $initializePaymentRequestTransfer->getPaymentProviderData());
    }
}
