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
     * @var array<\Generated\Shared\Transfer\PaymentTransmissionTransfer>
     */
    protected array $failedPaymentTransmissionTransfers = [];

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
        // In case all payment transmissions fail, we do not request the platform tzo do something, and we need to return a response with the failed ones.
        $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();

        try {
            $paymentsTransmissionsRequestTransfer = $this->addAppConfigToRequest($paymentsTransmissionsRequestTransfer);
            $paymentsTransmissionsRequestTransfer = $this->addPaymentsTransmissions($paymentsTransmissionsRequestTransfer);

            if ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions()->count() > 0) {
                $paymentsTransmissionsResponseTransfer = $this->platformPlugin->transferPayments($paymentsTransmissionsRequestTransfer);
            }
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

        /** @var \Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer $paymentsTransmissionsResponseTransfer */
        $paymentsTransmissionsResponseTransfer = $this->getTransactionHandler()->handleTransaction(function () use ($paymentsTransmissionsRequestTransfer, $paymentsTransmissionsResponseTransfer) {
            if ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions()->count() === 0) {
                // If there are no payments to transfer, we do not need to save anything. In such case, we most likely filtered out all orderItems that were passed.
                return $paymentsTransmissionsResponseTransfer;
            }

            $this->savePaymentsTransfers($paymentsTransmissionsResponseTransfer);

            return $paymentsTransmissionsResponseTransfer;
        });

        // Adding the failed ones to the response to give the Tenant the chance to see why the transfer failed.
        foreach ($paymentsTransmissionsRequestTransfer->getFailedPaymentsTransmissions() as $failedPaymentsTransmission) {
            $paymentsTransmissionsResponseTransfer->addPaymentTransmission($failedPaymentsTransmission);
        }

        return $paymentsTransmissionsResponseTransfer;
    }

    protected function addAppConfigToRequest(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsRequestTransfer
    {
        return $paymentsTransmissionsRequestTransfer->setAppConfigOrFail(
            $this->appConfigLoader->loadAppConfig($paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail()),
        );
    }

    /**
     * In case of a transfer reversal:
     * - The OrderItems contain a transferId of the previously made transfer that was sent to the Tenant.
     * - Foreach transferId we need to group the items.
     */
    protected function addPaymentsTransmissions(
        PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
    ): PaymentsTransmissionsRequestTransfer {
        $paymentsTransmissionsRequestTransfer = $this->addPaymentTransmissionsForOrderItemsGroupedByOrderReference($paymentsTransmissionsRequestTransfer);
        $paymentsTransmissionsRequestTransfer = $this->addPaymentTransmissionsForOrderItemsGroupedByTransferId($paymentsTransmissionsRequestTransfer);

        // Apply group plugin from other modules to split the payment transmissions
        foreach ($this->paymentsTransmissionsRequestExpanderPlugins as $paymentsTransmissionsRequestExpanderPlugin) {
            $paymentsTransmissionsRequestTransfer = $paymentsTransmissionsRequestExpanderPlugin->extendPaymentsTransmissionsRequest($paymentsTransmissionsRequestTransfer);
        }

        return $this->recalculatePaymentsTransmissions($paymentsTransmissionsRequestTransfer);
    }

    protected function addPaymentTransmissionsForOrderItemsGroupedByOrderReference(
        PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
    ): PaymentsTransmissionsRequestTransfer {
        $orderItemsGroupedByOrderReference = $this->getOrderItemsGroupedByOrderReference($paymentsTransmissionsRequestTransfer);

        if ($orderItemsGroupedByOrderReference === []) {
            return $paymentsTransmissionsRequestTransfer;
        }

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            array_keys($orderItemsGroupedByOrderReference),
        );

        foreach ($orderItemsGroupedByOrderReference as $orderReference => $orderItems) {
            $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection($paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(), $orderReference, $paymentTransferCollection);
            $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $orderItems);

            $paymentsTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
        }

        return $paymentsTransmissionsRequestTransfer;
    }

    protected function addPaymentTransmissionsForOrderItemsGroupedByTransferId(
        PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
    ): PaymentsTransmissionsRequestTransfer {
        $orderItemsGroupedByTransferId = $this->getOrderItemsGroupedByTransferIdAndOrderReference($paymentsTransmissionsRequestTransfer);

        if ($orderItemsGroupedByTransferId === []) {
            return $paymentsTransmissionsRequestTransfer;
        }

        $orderReferences = $this->getOrderReferencesFromOrderItemsGroupedByTransferId($orderItemsGroupedByTransferId);

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            $orderReferences,
        );

        // Collect all previous PaymentTransmissions for the given TransferIds
        $previousPaymentTransmissionTransfers = $this->appPaymentRepository->findPaymentTransmissionsByTransferIds(
            array_keys($orderItemsGroupedByTransferId),
        );

        foreach ($orderItemsGroupedByTransferId as $transferId => $orders) {
            foreach ($orders as $orderReference => $orderItems) {
                $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection(
                    $paymentsTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
                    $orderReference,
                    $paymentTransferCollection,
                );

                $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $orderItems);
                $paymentTransmissionTransfer->setTransferId($transferId);

                if (!isset($previousPaymentTransmissionTransfers[$transferId])) {
                    $paymentTransmissionTransfer
                        ->setIsSuccessful(false)
                        ->setMessage(MessageBuilder::paymentTransferByTransferIdNotFound($transferId));

                    $paymentsTransmissionsRequestTransfer->addFailedPaymentTransmission($paymentTransmissionTransfer);

                    continue;
                }

                $paymentsTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
            }
        }

        return $paymentsTransmissionsRequestTransfer;
    }

    /**
     * @param array<string, string> $orderItems
     */
    protected function createPaymentTransmissionTransfer(
        GeneratedPaymentTransfer $generatedPaymentTransfer,
        string $orderReference,
        array $orderItems
    ): PaymentTransmissionTransfer {
        $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $paymentTransmissionTransfer
            ->setOrderReference($orderReference)
            ->setTenantIdentifier($generatedPaymentTransfer->getTenantIdentifierOrFail())
            ->setTransactionId($generatedPaymentTransfer->getTransactionIdOrFail())
            ->setPayment($generatedPaymentTransfer)
            ->setOrderItems(new ArrayObject($orderItems));

        return $paymentTransmissionTransfer;
    }

    /**
     * Group orderItems by their transferId and inside of this by their orderReference. They were transferred together in the payout process.
     *
     * @return array<string, array>
     */
    protected function getOrderItemsGroupedByTransferIdAndOrderReference(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): array
    {
        $ordersGroupedByTransferIdAndOrderReference = [];

        foreach ($paymentsTransmissionsRequestTransfer->getOrderItems() as $orderItemTransfer) {
            if ($orderItemTransfer->getTransferId() === null) {
                continue;
            }

            if (!isset($ordersGroupedByTransferIdAndOrderReference[$orderItemTransfer->getTransferId()])) {
                $ordersGroupedByTransferIdAndOrderReference[$orderItemTransfer->getTransferId()] = [];
            }

            if (!isset($ordersGroupedByTransferIdAndOrderReference[$orderItemTransfer->getTransferId()][$orderItemTransfer->getOrderReference()])) {
                $ordersGroupedByTransferIdAndOrderReference[$orderItemTransfer->getTransferId()][$orderItemTransfer->getOrderReference()] = [];
            }

            $ordersGroupedByTransferIdAndOrderReference[$orderItemTransfer->getTransferId()][$orderItemTransfer->getOrderReference()][] = $orderItemTransfer;
        }

        return $ordersGroupedByTransferIdAndOrderReference;
    }

    /**
     * @param array<string, array> $orderItemsGroupedByTransferId
     *
     * @return array<string, string>
     */
    protected function getOrderReferencesFromOrderItemsGroupedByTransferId(array $orderItemsGroupedByTransferId): array
    {
        $orderReferences = [];

        foreach ($orderItemsGroupedByTransferId as $orderItemsGrouped) {
            foreach (array_keys($orderItemsGrouped) as $orderReference) {
                $orderReferences[$orderReference] = $orderReference;
            }
        }

        return $orderReferences;
    }

    /**
     * Group orderItems by their orderReference. Items with a transferId are ignored as they are grouped into a different stack
     *
     * @return array<string, array>
     */
    protected function getOrderItemsGroupedByOrderReference(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): array
    {
        $ordersGroupedByOrderReference = [];

        foreach ($paymentsTransmissionsRequestTransfer->getOrderItems() as $orderItemTransfer) {
            if ($orderItemTransfer->getTransferId() !== null) {
                continue;
            }

            if (!isset($ordersGroupedByOrderReference[$orderItemTransfer->getOrderReference()])) {
                $ordersGroupedByOrderReference[$orderItemTransfer->getOrderReference()] = [];
            }

            $ordersGroupedByOrderReference[$orderItemTransfer->getOrderReference()][$orderItemTransfer->getItemReference()] = $orderItemTransfer;
        }

        return $ordersGroupedByOrderReference;
    }

    protected function savePaymentsTransfers(
        PaymentsTransmissionsResponseTransfer $paymentsTransmissionsResponseTransfer
    ): PaymentsTransmissionsResponseTransfer {
        foreach ($paymentsTransmissionsResponseTransfer->getPaymentsTransmissions() as $paymentsTransmissionTransfer) {
            if (!$paymentsTransmissionTransfer->getIsSuccessful()) {
                continue;
            }

            $this->appPaymentEntityManager->savePaymentTransfer($paymentsTransmissionTransfer);
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
        foreach ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions() as $paymentsTransmissionTransfer) {
            $totalAmount = 0;
            $itemReferences = [];

            foreach ($paymentsTransmissionTransfer->getOrderItems() as $orderItemTransfer) {
                $totalAmount += $orderItemTransfer->getAmount();
                $itemReferences[] = $orderItemTransfer->getItemReference();
            }

            $paymentsTransmissionTransfer->setAmount($totalAmount);
            $paymentsTransmissionTransfer->setItemReferences($itemReferences);
        }

        return $paymentsTransmissionsRequestTransfer;
    }
}
