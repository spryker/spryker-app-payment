<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;

interface AppPaymentEntityManagerInterface
{
    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer;

    public function updatePayment(PaymentTransfer $paymentTransfer): PaymentTransfer;

    public function savePaymentTransfer(PaymentTransmissionTransfer $paymentTransmissionTransfer): PaymentTransmissionTransfer;

    public function createPaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer;

    public function updatePaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer;

    public function deletePaymentCollection(
        PaymentCollectionDeleteCriteriaTransfer $paymentCollectionDeleteCriteriaTransfer
    ): void;

    public function updatePaymentTransactionId(PaymentTransfer $paymentTransfer, string $transactionId): void;

    public function savePaymentStatusHistory(PaymentTransfer $paymentTransfer): void;
}
