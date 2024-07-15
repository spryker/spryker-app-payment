<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
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
 * @group PaymentRefundedTest
 * Add your own group annotations below this line
 */
class PaymentRefundedTest extends Unit
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
    public function testPaymentRefundedMessageIsSentViaWebhookHandlingForSucceededRefundWebhook(
        string $paymentRefundStatus
    ): void {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);
        $refundPaymentTransfer = $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, $paymentRefundStatus);

        $paymentRefundedTransfer = $this->tester->havePaymentRefundedTransfer([
            PaymentRefundedTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
            PaymentRefundedTransfer::ORDER_ITEM_IDS => $refundPaymentTransfer->getOrderItemIds(),
            PaymentRefundedTransfer::AMOUNT => $refundPaymentTransfer->getAmount(),
        ]);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::REFUND)
            ->setRefund((new PaymentRefundTransfer())->setRefundId($refundId))
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer) use ($transactionId): WebhookResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($webhookRequestTransfer->getPayment());
                $this->assertNotNull($webhookRequestTransfer->getAppConfig());
                $this->assertSame($webhookRequestTransfer->getTransactionId(), $transactionId);

                $webhookResponseTransfer = new WebhookResponseTransfer();
                $webhookResponseTransfer->setIsSuccessful(true);
                $webhookResponseTransfer->setRefundStatus(PaymentRefundStatus::SUCCEEDED);

                return $webhookResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel(
            $paymentRefundedTransfer,
            'payment-events',
            function (PaymentRefundedTransfer $messageTransfer, PaymentRefundedTransfer $sentMessageTransfer): void {
                $this->assertSame($messageTransfer->getOrderReference(), $sentMessageTransfer->getOrderReference());
                $this->assertEqualsCanonicalizing($messageTransfer->getOrderItemIds(), $sentMessageTransfer->getOrderItemIds());
                $this->assertSame($messageTransfer->getAmount(), $sentMessageTransfer->getAmount());
            },
        );
    }

    public function refundStatusDataProvider(): array
    {
        return [
            'succeeded refund' => [PaymentRefundStatus::SUCCEEDED],
            'failed refund' => [PaymentRefundStatus::FAILED],
            'canceled refund' => [PaymentRefundStatus::CANCELED],
            'pending refund' => [PaymentRefundStatus::PENDING],
            'processing refund' => [PaymentRefundStatus::PROCESSING],
        ];
    }
}
