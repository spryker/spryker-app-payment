<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentEvents
 * @group PaymentCapturedTest
 * Add your own group annotations below this line
 */
class PaymentCapturedTest extends Unit
{
    protected AppPaymentAsyncApiTester $tester;

    /**
     * The PaymentCaptured message is sent when the payment should be confirmed and the PaymentPluginInterface implementation returns a successful response.
     *
     * This action can only be made when the payment is in status PaymentStatusEnum::STATUS_AUTHORIZED
     */
    public function testPaymentCapturedMessageIsSend(): void
    {
        // Arrange
        $paymentCapturedTransfer = $this->tester->havePaymentCapturedTransfer();

        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::PAYMENT)
            ->setTransactionId($transactionId);

        $this->tester->mockPlatformPlugin(PaymentStatus::STATUS_CAPTURED);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentCapturedTransfer, 'payment-events');
    }
}
