<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence\Propel\Payment\Mapper;

use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentRefund;
use Propel\Runtime\Collection\Collection;

class PaymentMapper
{
    public function mapPaymentTransferToPaymentEntity(PaymentTransfer $paymentTransfer, SpyPayment $spyPayment): SpyPayment
    {
        $quoteTransfer = $paymentTransfer->getQuoteOrFail();
        $quoteJson = json_encode($quoteTransfer->toArray());

        $paymentData = $paymentTransfer->modifiedToArray();
        $paymentData[PaymentTransfer::QUOTE] = $quoteJson;

        return $spyPayment->fromArray($paymentData);
    }

    public function mapPaymentEntityToPaymentTransfer(SpyPayment $spyPayment, PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $quoteData = json_decode((string)$spyPayment->getQuote(), true);

        $paymentData = $spyPayment->toArray();
        $paymentData[PaymentTransfer::QUOTE] = $quoteData;

        return $paymentTransfer->fromArray($paymentData, true);
    }

    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\AppPayment\Persistence\SpyPaymentRefund> $paymentRefundEntityCollection
     *
     * @return list<\Generated\Shared\Transfer\PaymentRefundTransfer>
     */
    public function mapPaymentRefundEntityCollectionToPaymentRefundTransfers(Collection $paymentRefundEntityCollection): array
    {
        $paymentRefundTransfers = [];
        foreach ($paymentRefundEntityCollection as $paymentRefundEntity) {
            $paymentRefundTransfers[] = $this->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, new PaymentRefundTransfer());
        }

        return $paymentRefundTransfers;
    }

    public function mapPaymentRefundEntityToPaymentRefundTransfer(
        SpyPaymentRefund $spyPaymentRefund,
        PaymentRefundTransfer $paymentRefundTransfer
    ): PaymentRefundTransfer {
        return $paymentRefundTransfer->fromArray($spyPaymentRefund->toArray(), true);
    }

    public function mapPaymentRefundTransferToPaymentRefundEntity(
        PaymentRefundTransfer $paymentRefundTransfer,
        SpyPaymentRefund $spyPaymentRefund
    ): SpyPaymentRefund {
        return $spyPaymentRefund->fromArray($paymentRefundTransfer->modifiedToArray());
    }
}
