<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentRefund;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentPersistenceFactory getFactory()
 */
class AppPaymentEntityManager extends AbstractEntityManager implements AppPaymentEntityManagerInterface
{
    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentMapper()->mapPaymentTransferToPaymentEntity($paymentTransfer, new SpyPayment());
        $spyPayment->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, $paymentTransfer);
    }

    public function savePayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentQuery()->findOneByTransactionId($paymentTransfer->getTransactionIdOrFail());

        if ($spyPayment === null) {
            throw new PaymentByTransactionIdNotFoundException($paymentTransfer->getTransactionIdOrFail());
        }

        $spyPayment = $this->getFactory()->createPaymentMapper()->mapPaymentTransferToPaymentEntity($paymentTransfer, $spyPayment);
        $spyPayment->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, $paymentTransfer);
    }

    public function createPaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer
    {
        $spyPaymentRefund = $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundTransferToPaymentRefundEntity($paymentRefundTransfer, (new SpyPaymentRefund()));
        $spyPaymentRefund->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentRefundEntityToPaymentRefundTransfer($spyPaymentRefund, $paymentRefundTransfer);
    }

    public function updatePaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer
    {
        $paymentRefundEntity = $this->getFactory()->createPaymentRefundQuery()
            ->findOneByRefundId($paymentRefundTransfer->getRefundIdOrFail());

        if ($paymentRefundEntity === null) {
            throw new RefundByRefundIdNotFoundException($paymentRefundTransfer->getRefundIdOrFail());
        }

        $paymentRefundEntity = $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundTransferToPaymentRefundEntity($paymentRefundTransfer, $paymentRefundEntity);
        $paymentRefundEntity->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, $paymentRefundTransfer);
    }

    public function deletePaymentCollection(
        PaymentCollectionDeleteCriteriaTransfer $paymentCollectionDeleteCriteriaTransfer
    ): void {
        $this->getFactory()
            ->createPaymentQuery()
            ->filterByTenantIdentifier($paymentCollectionDeleteCriteriaTransfer->getTenantIdentifierOrFail())
            ->delete();
    }
}
