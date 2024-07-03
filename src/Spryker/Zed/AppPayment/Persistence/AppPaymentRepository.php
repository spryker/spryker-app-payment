<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransfer;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentPersistenceFactory getFactory()
 */
class AppPaymentRepository extends AbstractRepository implements AppPaymentRepositoryInterface
{
    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException
     */
    public function getPaymentByTransactionId(string $transactionId): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentQuery()->findOneByTransactionId($transactionId);

        if ($spyPayment === null) {
            throw new PaymentByTransactionIdNotFoundException($transactionId);
        }

        return $this->mapPaymentEntityToPaymentTransfer($spyPayment);
    }

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException
     */
    public function getPaymentByTenantIdentifierAndOrderReference(string $tenantIdentifier, string $orderReference): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentQuery()->filterByTenantIdentifier($tenantIdentifier)
            ->filterByOrderReference($orderReference)
            ->findOne();

        if ($spyPayment === null) {
            throw new PaymentByTenantIdentifierAndOrderReferenceNotFoundException($tenantIdentifier, $orderReference);
        }

        return $this->mapPaymentEntityToPaymentTransfer($spyPayment);
    }

    /**
     * @param array<string> $orderReferences
     *
     * @return array<\Generated\Shared\Transfer\PaymentTransfer>
     */
    public function getPaymentsByTenantIdentifierAndOrderReferences(string $tenantIdentifier, array $orderReferences): array
    {
        /** @var array<\Orm\Zed\AppPayment\Persistence\SpyPayment> $spyPaymentCollection */
        $spyPaymentCollection = $this->getFactory()->createPaymentQuery()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->filterByOrderReference_In($orderReferences)
            ->find();

        $paymentTransfers = [];

        foreach ($spyPaymentCollection as $spyPaymentEntity) {
            $paymentTransfers[] = $this->mapPaymentEntityToPaymentTransfer($spyPaymentEntity);
        }

        return $paymentTransfers;
    }

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException
     */
    public function getRefundByRefundId(string $refundId): PaymentRefundTransfer
    {
        $paymentRefundEntity = $this->getFactory()->createPaymentRefundQuery()->findOneByRefundId($refundId);

        if ($paymentRefundEntity === null) {
            throw new RefundByRefundIdNotFoundException($refundId);
        }

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, new PaymentRefundTransfer());
    }

    public function getRefundsByTransactionIdAndOrderItemIdAndStatuses(
        string $transactionId,
        array $orderItemIds,
        array $refundStatuses
    ): array {
        $paymentRefundEntityCollection = $this->getFactory()->createPaymentRefundQuery()
            ->filterByTransactionId($transactionId)
            ->filterByOrderItemIds($orderItemIds, Criteria::CONTAINS_SOME)
            ->filterByStatus_In($refundStatuses)
            ->find();

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundEntityCollectionToPaymentRefundTransfers($paymentRefundEntityCollection);
    }

    protected function mapPaymentEntityToPaymentTransfer(SpyPayment $spyPayment): PaymentTransfer
    {
        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, new PaymentTransfer());
    }

    public function findPaymentTransmissionByTransactionId(string $getTransactionIdOrFail): ?PaymentTransmissionTransfer
    {
        $paymentTransferEntity = $this->getFactory()->createPaymentTransferQuery()->findOneByTransactionId($getTransactionIdOrFail);

        if (!($paymentTransferEntity instanceof SpyPaymentTransfer)) {
            return null;
        }

        return $this->getFactory()->createPaymentMapper()->mapPaymentTransmissionEntityToPaymentTransmissionTransfer($paymentTransferEntity, new PaymentTransmissionTransfer());
    }
}
