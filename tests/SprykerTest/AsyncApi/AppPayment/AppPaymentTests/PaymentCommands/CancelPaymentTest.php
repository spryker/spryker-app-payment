<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\AppPaymentTests\PaymentCommands;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group AppPaymentTests
 * @group PaymentCommands
 * @group CancelPaymentTest
 * Add your own group annotations below this line
 */
class CancelPaymentTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    /**
     * Cancellable states are all states before a payment is captured. When a payment is captured, it cannot be cancelled anymore and only the refund operation is possible.
     * This test will check if the payment is updated to PaymentStatusEnum::STATUS_CANCELLED when the CancelPayment message is received and the payment is in a cancellable state.
     */
    public function testCancelPaymentMessageUpdatesPaymentToPaymentCancelledWhenPaymentIsInCancellableStateAndSendsPaymentCanceledMessage(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_NEW);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $cancelPaymentResponseTransfer = (new CancelPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CANCELED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => function (CancelPaymentRequestTransfer $cancelPaymentRequestTransfer) use ($cancelPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPaymentRequestTransfer->getPayment());

                return $cancelPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($cancelPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CANCELED);
        $this->tester->assertMessageWasSent(PaymentCanceledTransfer::class);
    }

    public function testCancelPaymentMessageDoesNotUpdatePaymentStatusWhenPaymentIsNotInCancellableStateAndSendsPaymentCancellationFailedMessage(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $cancelPaymentResponseTransfer = (new CancelPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CANCELED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => function (CancelPaymentRequestTransfer $cancelPaymentRequestTransfer) use ($cancelPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPaymentRequestTransfer->getPayment());

                return $cancelPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($cancelPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURED);
        $this->tester->assertMessageWasSent(PaymentCancellationFailedTransfer::class);
    }

    public function testCancelPaymentMessageUpdatePaymentStatusToCanceledWhenPaymentIsInStateCancellationFailedAndSendsPaymentCanceledMessage(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CANCELLATION_FAILED);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $cancelPaymentResponseTransfer = (new CancelPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CANCELED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => function (CancelPaymentRequestTransfer $cancelPaymentRequestTransfer) use ($cancelPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPaymentRequestTransfer->getPayment());

                return $cancelPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($cancelPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CANCELED);
        $this->tester->assertMessageWasSent(PaymentCanceledTransfer::class);
    }

    public function testHandleCancelPaymentUpdatesPaymentToCancellationFailedWhenPlatformPluginThrowsException(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => static function (): never {
                throw new Exception();
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->runMessageReceiveTest($cancelPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CANCELLATION_FAILED);
    }
}
