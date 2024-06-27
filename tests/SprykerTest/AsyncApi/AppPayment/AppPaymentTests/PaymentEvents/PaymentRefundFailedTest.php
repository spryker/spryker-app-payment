<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentEvents
 * @group PaymentRefundFailedTest
 * Add your own group annotations below this line
 */
class PaymentRefundFailedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    /**
     * @dataProvider refundStatusDataProvider
     *
     * @param \Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus $paymentRefundStatus
     *
     * @return void
     */
    public function testPaymentRefundFailedMessageIsSentViaWebhookHandlingForFailedRefundWebhook(
        string $paymentRefundStatus
    ): void {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);
        $refundPaymentTransfer = $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId);

        $paymentRefundFailedTransfer = $this->tester->havePaymentRefundFailedTransfer([
            PaymentRefundedTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
            PaymentRefundedTransfer::ORDER_ITEM_IDS => $refundPaymentTransfer->getOrderItemIds(),
            PaymentRefundedTransfer::AMOUNT => $refundPaymentTransfer->getAmount(),
        ]);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::REFUND)
            ->setRefund((new PaymentRefundTransfer())->setRefundId($refundId))
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer) use ($transactionId, $paymentRefundStatus): WebhookResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($webhookRequestTransfer->getPayment());
                $this->assertNotNull($webhookRequestTransfer->getAppConfig());
                $this->assertSame($webhookRequestTransfer->getTransactionId(), $transactionId);

                $webhookResponseTransfer = new WebhookResponseTransfer();
                $webhookResponseTransfer->setIsSuccessful(true);
                $webhookResponseTransfer->setRefundStatus($paymentRefundStatus);

                return $webhookResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel(
            $paymentRefundFailedTransfer,
            'payment-events',
            function (PaymentRefundFailedTransfer $messageTransfer, PaymentRefundFailedTransfer $sentMessageTransfer): void {
                $this->assertSame($messageTransfer->getOrderReference(), $sentMessageTransfer->getOrderReference());
                $this->assertEqualsCanonicalizing($messageTransfer->getOrderItemIds(), $sentMessageTransfer->getOrderItemIds());
                $this->assertSame($messageTransfer->getAmount(), $sentMessageTransfer->getAmount());
            },
        );
    }

    public function refundStatusDataProvider(): array
    {
        return [
            'failed refund' => [PaymentRefundStatus::FAILED],
            'canceled refund' => [PaymentRefundStatus::CANCELED],
        ];
    }
}
