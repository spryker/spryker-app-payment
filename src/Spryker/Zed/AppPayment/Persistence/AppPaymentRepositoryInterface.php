<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;

interface AppPaymentRepositoryInterface
{
    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException
     */
    public function getPaymentByTransactionId(string $transactionId): PaymentTransfer;

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException
     */
    public function getPaymentByTenantIdentifierAndOrderReference(string $tenantIdentifier, string $orderReference): PaymentTransfer;

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException
     */
    public function getRefundByRefundId(string $refundId): PaymentRefundTransfer;

    /**
     * @param array<string> $orderItemIds
     * @param array<string> $refundStatuses
     *
     * @return array<\Generated\Shared\Transfer\PaymentRefundTransfer>
     */
    public function getRefundsByTransactionIdAndOrderItemIdAndStatuses(
        string $transactionId,
        array $orderItemIds,
        array $refundStatuses
    ): array;
}
