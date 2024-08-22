<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Transfer;

use ArrayObject;
use Generated\Shared\Transfer\PaymentTransfer as GeneratedPaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformMarketplacePluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
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
     * @param array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface> $paymentTransmissionsRequestExpanderPlugins
     */
    public function __construct(
        protected AppPaymentPlatformMarketplacePluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader,
        protected array $paymentTransmissionsRequestExpanderPlugins
    ) {
    }

    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer
    {
        // In case all payment transmissions fail, we do not request the platform to do something, and we need to return a response with the failed ones.
        $paymentTransmissionsResponseTransfer = new PaymentTransmissionsResponseTransfer();

        try {
            $paymentTransmissionsRequestTransfer = $this->addAppConfigToRequest($paymentTransmissionsRequestTransfer);
            $paymentTransmissionsRequestTransfer = $this->addPaymentTransmissions($paymentTransmissionsRequestTransfer);

            if ($paymentTransmissionsRequestTransfer->getPaymentTransmissions()->count() > 0) {
                $paymentTransmissionsResponseTransfer = $this->appPaymentPlatformPlugin->transferPayments($paymentTransmissionsRequestTransfer);
            }
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransmissionsRequestTransfer::TENANT_IDENTIFIER => $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $paymentTransmissionsResponseTransfer = new PaymentTransmissionsResponseTransfer();
            $paymentTransmissionsResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $paymentTransmissionsResponseTransfer;
        }

        /** @var \Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer */
        $paymentTransmissionsResponseTransfer = $this->getTransactionHandler()->handleTransaction(function () use ($paymentTransmissionsRequestTransfer, $paymentTransmissionsResponseTransfer) {
            if ($paymentTransmissionsRequestTransfer->getPaymentTransmissions()->count() === 0) {
                // If there are no payments to transfer, we do not need to save anything. In such case, we most likely filtered out all orderItems that were passed.
                return $paymentTransmissionsResponseTransfer;
            }

            $this->savePaymentsTransfers($paymentTransmissionsResponseTransfer);

            return $paymentTransmissionsResponseTransfer;
        });

        // Adding the failed ones to the response to give the Tenant the chance to see why the transfer failed.
        foreach ($paymentTransmissionsRequestTransfer->getFailedPaymentTransmissions() as $failedPaymentTransmission) {
            $paymentTransmissionsResponseTransfer->addPaymentTransmission($failedPaymentTransmission);
        }

        return $paymentTransmissionsResponseTransfer;
    }

    protected function addAppConfigToRequest(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsRequestTransfer
    {
        return $paymentTransmissionsRequestTransfer->setAppConfigOrFail(
            $this->appConfigLoader->loadAppConfig($paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail()),
        );
    }

    /**
     * In case of a transfer reversal:
     * - The OrderItems contain a transferId of the previously made transfer that was sent to the Tenant.
     * - Foreach transferId we need to group the items.
     */
    protected function addPaymentTransmissions(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $paymentTransmissionsRequestTransfer = $this->addPaymentTransmissionsForOrderItemsGroupedByOrderReference($paymentTransmissionsRequestTransfer);
        $paymentTransmissionsRequestTransfer = $this->addPaymentTransmissionsForOrderItemsGroupedByTransferId($paymentTransmissionsRequestTransfer);

        // Apply group plugin from other modules to split the payment transmissions
        foreach ($this->paymentTransmissionsRequestExpanderPlugins as $paymentTransmissionsRequestExpanderPlugin) {
            $paymentTransmissionsRequestTransfer = $paymentTransmissionsRequestExpanderPlugin->extendPaymentTransmissionsRequest($paymentTransmissionsRequestTransfer);
        }

        return $this->recalculatePaymentTransmissions($paymentTransmissionsRequestTransfer);
    }

    protected function addPaymentTransmissionsForOrderItemsGroupedByOrderReference(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $orderItemsGroupedByOrderReference = $this->getOrderItemsGroupedByOrderReference($paymentTransmissionsRequestTransfer);

        if ($orderItemsGroupedByOrderReference === []) {
            return $paymentTransmissionsRequestTransfer;
        }

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            array_keys($orderItemsGroupedByOrderReference),
        );

        foreach ($orderItemsGroupedByOrderReference as $orderReference => $orderItems) {
            $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection($paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(), $orderReference, $paymentTransferCollection);
            $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $orderItems);

            $paymentTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
        }

        return $paymentTransmissionsRequestTransfer;
    }

    protected function addPaymentTransmissionsForOrderItemsGroupedByTransferId(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $orderItemsGroupedByTransferId = $this->getOrderItemsGroupedByTransferIdAndOrderReference($paymentTransmissionsRequestTransfer);

        if ($orderItemsGroupedByTransferId === []) {
            return $paymentTransmissionsRequestTransfer;
        }

        $orderReferences = $this->getOrderReferencesFromOrderItemsGroupedByTransferId($orderItemsGroupedByTransferId);

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            $orderReferences,
        );

        // Collect all previous PaymentTransmissions for the given TransferIds
        $previousPaymentTransmissionTransfers = $this->appPaymentRepository->findPaymentTransmissionsByTransferIds(
            array_keys($orderItemsGroupedByTransferId),
        );

        foreach ($orderItemsGroupedByTransferId as $transferId => $orders) {
            foreach ($orders as $orderReference => $orderItems) {
                $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection(
                    $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
                    $orderReference,
                    $paymentTransferCollection,
                );

                $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $orderItems);
                $paymentTransmissionTransfer->setTransferId($transferId);

                if (!isset($previousPaymentTransmissionTransfers[$transferId])) {
                    $paymentTransmissionTransfer
                        ->setIsSuccessful(false)
                        ->setMessage(MessageBuilder::paymentTransferByTransferIdNotFound($transferId));

                    $paymentTransmissionsRequestTransfer->addFailedPaymentTransmission($paymentTransmissionTransfer);

                    continue;
                }

                $paymentTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
            }
        }

        return $paymentTransmissionsRequestTransfer;
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
    protected function getOrderItemsGroupedByTransferIdAndOrderReference(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): array
    {
        $ordersGroupedByTransferIdAndOrderReference = [];

        foreach ($paymentTransmissionsRequestTransfer->getOrderItems() as $orderItemTransfer) {
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
    protected function getOrderItemsGroupedByOrderReference(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): array
    {
        $ordersGroupedByOrderReference = [];

        foreach ($paymentTransmissionsRequestTransfer->getOrderItems() as $orderItemTransfer) {
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
        PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer
    ): PaymentTransmissionsResponseTransfer {
        foreach ($paymentTransmissionsResponseTransfer->getPaymentTransmissions() as $paymentTransmission) {
            if (!$paymentTransmission->getIsSuccessful()) {
                continue;
            }

            $this->appPaymentEntityManager->savePaymentTransfer($paymentTransmission);
        }

        return $paymentTransmissionsResponseTransfer;
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

            if ($paymentTransfer->getOrderReferenceOrFail() !== $orderReference) {
                continue;
            }

            // @codeCoverageIgnoreEnd

            return $paymentTransfer;
        }

        throw new PaymentByTenantIdentifierAndOrderReferenceNotFoundException(MessageBuilder::paymentByTenantIdentifierAndOrderReferenceNotFound($tenantIdentifier, $orderReference));
    }

    protected function recalculatePaymentTransmissions(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        foreach ($paymentTransmissionsRequestTransfer->getPaymentTransmissions() as $paymentTransmission) {
            $totalAmount = 0;
            $itemReferences = [];

            foreach ($paymentTransmission->getOrderItems() as $orderItemTransfer) {
                $totalAmount += $orderItemTransfer->getAmount();
                $itemReferences[] = $orderItemTransfer->getItemReference();
            }

            $paymentTransmission->setAmount($totalAmount);
            $paymentTransmission->setItemReferences($itemReferences);
        }

        return $paymentTransmissionsRequestTransfer;
    }
}
