<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Transfer;

use ArrayObject;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer as GeneratedPaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentTransfer
{
    use TransactionTrait;
    use LoggerTrait;

    /**
     * @param array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentsTransmissionsRequestExtenderPluginInterface> $paymentsTransmissionsRequestExpanderPlugins
     */
    public function __construct(
        protected PlatformPluginInterface $platformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader,
        protected array $paymentsTransmissionsRequestExpanderPlugins
    ) {
    }

    public function transferPayments(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsResponseTransfer
    {
        try {
            $paymentsTransmissionsRequestTransfer = $this->addAppConfigToRequest($paymentsTransmissionsRequestTransfer);
            $paymentsTransmissionsRequestTransfer = $this->addPaymentsTransmissions($paymentsTransmissionsRequestTransfer);

            $paymentsTransmissionsResponseTransfer = $this->platformPlugin->transferPayments($paymentsTransmissionsRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentsTransmissionsRequestTransfer::TENANT_IDENTIFIER => $paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();
            $paymentsTransmissionsResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $paymentsTransmissionsResponseTransfer;
        }

        if ($paymentsTransmissionsResponseTransfer->getIsSuccessful() !== true) {
            return $paymentsTransmissionsResponseTransfer;
        }

        /** @phpstan-var \Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($paymentsTransmissionsResponseTransfer) {
            $this->savePaymentsTransfers($paymentsTransmissionsResponseTransfer);

            return $paymentsTransmissionsResponseTransfer;
        });
    }

    protected function addAppConfigToRequest(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsRequestTransfer
    {
        return $paymentsTransmissionsRequestTransfer->setAppConfigOrFail(
            $this->appConfigLoader->loadAppConfig($paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail()),
        );
    }

    protected function addPaymentsTransmissions(
        PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
    ): PaymentsTransmissionsRequestTransfer {
        $orders = [];

        foreach ($paymentsTransmissionsRequestTransfer->getOrderItems() as $orderItemTransfer) {
            if (!isset($orders[$orderItemTransfer->getOrderReference()])) {
                $orders[$orderItemTransfer->getOrderReference()] = [];
            }

            $orders[$orderItemTransfer->getOrderReference()][$orderItemTransfer->getItemReference()] = $orderItemTransfer;
        }

        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            array_keys($orders),
        );

        // For each Order === Payment add a PaymentTransmission
        foreach ($orders as $orderReference => $orderItems) {
            $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection($paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(), $orderReference, $paymentTransferCollection);

            $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
            $paymentTransmissionTransfer
                ->setOrderReference($orderReference)
                ->setTenantIdentifier($paymentTransfer->getTenantIdentifierOrFail())
                ->setTransactionId($paymentTransfer->getTransactionIdOrFail())
                ->setPayment($paymentTransfer)
                ->setOrderItems(new ArrayObject($orderItems));

            $paymentsTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
        }

        // Apply group plugin from other modules to split the payment transmissions
        foreach ($this->paymentsTransmissionsRequestExpanderPlugins as $paymentsTransmissionsRequestExpanderPlugin) {
            $paymentsTransmissionsRequestTransfer = $paymentsTransmissionsRequestExpanderPlugin->extendPaymentsTransmissionsRequest($paymentsTransmissionsRequestTransfer);
        }

        return $this->recalculatePaymentsTransmissions($paymentsTransmissionsRequestTransfer);
    }

    protected function savePaymentsTransfers(
        PaymentsTransmissionsResponseTransfer $paymentsTransmissionsResponseTransfer
    ): PaymentsTransmissionsResponseTransfer {
        foreach ($paymentsTransmissionsResponseTransfer->getPaymentsTransmissions() as $paymentsTransmission) {
            $this->appPaymentEntityManager->savePaymentTransfer($paymentsTransmission);
        }

        return $paymentsTransmissionsResponseTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentTransfer> $paymentTransferCollection
     *
     * @throws \Spryker\Zed\AppPayment\Business\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException
     */
    protected function getPaymentByTenantIdentifierAndOrderReferenceFromCollection(
        string $tenantIdentifier,
        string $orderReference,
        array $paymentTransferCollection
    ): GeneratedPaymentTransfer {
        foreach ($paymentTransferCollection as $paymentTransfer) {
            // This will never be not set, but we need to check it for static analysis
            // @codeCoverageIgnoreStart
            if ($paymentTransfer->getTenantIdentifierOrFail() !== $tenantIdentifier) {
                continue;
            }

            // @codeCoverageIgnoreEnd

            if ($paymentTransfer->getOrderReferenceOrFail() !== $orderReference) {
                continue;
            }

            return $paymentTransfer;
        }

        throw new PaymentByTenantIdentifierAndOrderReferenceNotFoundException(MessageBuilder::paymentByTenantIdentifierAndOrderReferenceNotFound($tenantIdentifier, $orderReference));
    }

    protected function recalculatePaymentsTransmissions(
        PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
    ): PaymentsTransmissionsRequestTransfer {
        foreach ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions() as $paymentsTransmission) {
            $totalAmount = 0;
            $itemReferences = [];

            foreach ($paymentsTransmission->getOrderItems() as $orderItemTransfer) {
                $totalAmount += $orderItemTransfer->getAmount();
                $itemReferences[] = $orderItemTransfer->getItemReference();
            }

            $paymentsTransmission->setAmount($totalAmount);
            $paymentsTransmission->setItemReferences($itemReferences);
        }

        return $paymentsTransmissionsRequestTransfer;
    }
}
