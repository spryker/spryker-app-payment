<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Business\Payment\Initialize;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\AppPayment\AppPaymentBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Business
 * @group Payment
 * @group Initialize
 * @group PreOrderPaymentInitializerTest
 * Add your own group annotations below this line
 */
class PreOrderPaymentInitializerTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBusinessTester $tester;

    public function testGivenPreOrderPaymentWasAlreadyDoneWhenInitializePaymentIsCalledAgainAndGrandTotalHasChangedAndTheTransactionIdChangesThenThePaymentsTransactionIdIsUpdated(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $newTransactionId = Uuid::uuid4()->toString();

        $this->tester->havePayment([
            PaymentTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $orderTransfer = $initializePaymentRequestTransfer->getOrderData();
        $orderTransfer->setOrderReference(null);

        $initializePaymentRequestTransfer->setOrderData($orderTransfer);

        $initializePaymentRequestTransfer->setPaymentProviderData([
            PaymentTransfer::TRANSACTION_ID => $transactionId, // required field to always be set in pre-order payments.
        ]);

        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($newTransactionId)
            ->setPaymentProviderData([
                PaymentTransfer::TRANSACTION_ID => $newTransactionId,
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
        $this->tester->getFacade()->initializePayment($initializePaymentRequestTransfer);

        // Assert
        $this->tester->dontSeePaymentWithTransactionId($transactionId);
        $this->tester->seePaymentWithTransactionId($newTransactionId);
    }
}
