<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\PaymentRefundBuilder;
use Generated\Shared\DataBuilder\RefundPaymentRequestBuilder;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManager;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepository;

class AppPaymentRefundHelper extends Module
{
    public function assertPaymentRefundIsInStatus(string $refundId, string $expectedStatus): void
    {
        $paymentRefundTransfer = (new AppPaymentRepository())->getRefundByRefundId($refundId);

        $this->assertSame(
            $expectedStatus,
            $paymentRefundTransfer->getStatus(),
            sprintf(
                'Expected payment refund to be in status "%s" but got "%s"',
                $expectedStatus,
                $paymentRefundTransfer->getStatus(),
            ),
        );
    }

    public function havePaymentRefundForTransactionIdAndRefundId(
        string $transactionId,
        string $refundId,
        ?string $paymentRefundStatus = PaymentRefundStatus::SUCCEEDED
    ): PaymentRefundTransfer {
        return $this->havePaymentRefund([
            PaymentRefundTransfer::TRANSACTION_ID => $transactionId,
            PaymentRefundTransfer::REFUND_ID => $refundId,
            PaymentRefundTransfer::STATUS => $paymentRefundStatus,
        ]);
    }

    public function havePaymentRefund(array $seedData = []): PaymentRefundTransfer
    {
        $paymentRefundTransfer = (new PaymentRefundBuilder($seedData))->build();

        return (new AppPaymentEntityManager())->createPaymentRefund($paymentRefundTransfer);
    }

    public function haveRefundPaymentRequestTransfer(array $seedData = []): RefundPaymentRequestTransfer
    {
        return (new RefundPaymentRequestBuilder($seedData))->build();
    }
}
