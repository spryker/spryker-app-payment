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
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader,
        protected array $paymentTransmissionsRequestExpanderPlugins
    ) {
    }

    public function transferPayments(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsResponseTransfer {
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
                // If there are no payments to transfer, we do not need to save anything. In such case, we most likely filtered out all payment transmission items that were passed.
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
     * - The payment transmission items contain a transferId of the previously made transfer that was sent to the Tenant.
     * - Foreach transferId we need to group the items.
     */
    protected function addPaymentTransmissions(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $paymentTransmissionsRequestTransfer = $this->addPaymentTransmissionsForPaymentTransmissionItemsGroupedByOrderReference($paymentTransmissionsRequestTransfer);
        $paymentTransmissionsRequestTransfer = $this->addPaymentTransmissionsForPaymentTransmissionItemsGroupedByTransferId($paymentTransmissionsRequestTransfer);

        // Apply group plugin from other modules to split the payment transmissions
        foreach ($this->paymentTransmissionsRequestExpanderPlugins as $paymentTransmissionsRequestExpanderPlugin) {
            $paymentTransmissionsRequestTransfer = $paymentTransmissionsRequestExpanderPlugin->extendPaymentTransmissionsRequest($paymentTransmissionsRequestTransfer);
        }

        return $this->recalculatePaymentTransmissions($paymentTransmissionsRequestTransfer);
    }

    protected function addPaymentTransmissionsForPaymentTransmissionItemsGroupedByOrderReference(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $paymentTransmissionItemsGroupedByOrderReference = $this->getPaymentTransmissionItemsGroupedByOrderReferenceAndItemReference($paymentTransmissionsRequestTransfer);
        if ($paymentTransmissionItemsGroupedByOrderReference === []) {
            return $paymentTransmissionsRequestTransfer;
        }

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            array_keys($paymentTransmissionItemsGroupedByOrderReference),
        );

        foreach ($paymentTransmissionItemsGroupedByOrderReference as $orderReference => $paymentTransmissionItems) {
            $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection($paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(), $orderReference, $paymentTransferCollection);
            $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $paymentTransmissionItems);

            $paymentTransmissionsRequestTransfer->addPaymentTransmission($paymentTransmissionTransfer);
        }

        return $paymentTransmissionsRequestTransfer;
    }

    protected function addPaymentTransmissionsForPaymentTransmissionItemsGroupedByTransferId(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer {
        $paymentTransmissionItemsGroupedByTransferIdAndOrderReference = $this->getTransmissionItemsGroupedByTransferIdAndOrderReference($paymentTransmissionsRequestTransfer);

        if ($paymentTransmissionItemsGroupedByTransferIdAndOrderReference === []) {
            return $paymentTransmissionsRequestTransfer;
        }

        $orderReferences = $this->getOrderReferencesFromPaymentTransmissionItemsGroupedByTransferId($paymentTransmissionItemsGroupedByTransferIdAndOrderReference);

        // Collect all payments for the given Tenant and OrderReferences.
        $paymentTransferCollection = $this->appPaymentRepository->getPaymentsByTenantIdentifierAndOrderReferences(
            $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
            $orderReferences,
        );

        // Collect all previous PaymentTransmissions for the given TransferIds
        $previousPaymentTransmissionTransfers = $this->appPaymentRepository->findPaymentTransmissionsByTransferIds(
            array_keys($paymentTransmissionItemsGroupedByTransferIdAndOrderReference),
        );

        foreach ($paymentTransmissionItemsGroupedByTransferIdAndOrderReference as $transferId => $orders) {
            foreach ($orders as $orderReference => $paymentTransmissionItems) {
                $paymentTransfer = $this->getPaymentByTenantIdentifierAndOrderReferenceFromCollection(
                    $paymentTransmissionsRequestTransfer->getTenantIdentifierOrFail(),
                    $orderReference,
                    $paymentTransferCollection,
                );

                $paymentTransmissionTransfer = $this->createPaymentTransmissionTransfer($paymentTransfer, $orderReference, $paymentTransmissionItems);
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
     * @param array<string|int, \Generated\Shared\Transfer\PaymentTransmissionItemTransfer> $paymentTransmissionItems
     */
    protected function createPaymentTransmissionTransfer(
        GeneratedPaymentTransfer $generatedPaymentTransfer,
        string $orderReference,
        array $paymentTransmissionItems
    ): PaymentTransmissionTransfer {
        $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $paymentTransmissionTransfer
            ->setOrderReference($orderReference)
            ->setTenantIdentifier($generatedPaymentTransfer->getTenantIdentifierOrFail())
            ->setTransactionId($generatedPaymentTransfer->getTransactionIdOrFail())
            ->setPayment($generatedPaymentTransfer)
            ->setPaymentTransmissionItems(new ArrayObject($paymentTransmissionItems));

        return $paymentTransmissionTransfer;
    }

    /**
     * Group payment transmission items(order items, order expenses and any additional type) by their transferId and inside of this by their orderReference. They were transferred together in the payout process.
     *
     * @return array<string, array<string, array<int, \Generated\Shared\Transfer\PaymentTransmissionItemTransfer>>>
     */
    protected function getTransmissionItemsGroupedByTransferIdAndOrderReference(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): array {
        $paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference = [];

        foreach ($paymentTransmissionsRequestTransfer->getPaymentTransmissionItems() as $paymentTransmissionItem) {
            if ($paymentTransmissionItem->getTransferId() === null) {
                continue;
            }

            if (!isset($paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference[$paymentTransmissionItem->getTransferId()])) {
                $paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference[$paymentTransmissionItem->getTransferId()] = [];
            }

            if (!isset($paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference[$paymentTransmissionItem->getTransferId()][$paymentTransmissionItem->getOrderReference()])) {
                $paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference[$paymentTransmissionItem->getTransferId()][$paymentTransmissionItem->getOrderReference()] = [];
            }

            $paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference[$paymentTransmissionItem->getTransferId()][$paymentTransmissionItem->getOrderReference()][] = $paymentTransmissionItem;
        }

        return $paymentTransmissionItemTransfersGroupedByTransferIdAndOrderReference;
    }

    /**
     * @param array<string, array<string, array<int, \Generated\Shared\Transfer\PaymentTransmissionItemTransfer>>> $paymentTransmissionItemsGroupedByTransferIdAndOrderReference
     *
     * @return array<string, string>
     */
    protected function getOrderReferencesFromPaymentTransmissionItemsGroupedByTransferId(
        array $paymentTransmissionItemsGroupedByTransferIdAndOrderReference
    ): array {
        $orderReferences = [];

        foreach ($paymentTransmissionItemsGroupedByTransferIdAndOrderReference as $paymentTransmissionItemsGrouped) {
            foreach (array_keys($paymentTransmissionItemsGrouped) as $orderReference) {
                $orderReferences[$orderReference] = $orderReference;
            }
        }

        return $orderReferences;
    }

    /**
     * Group payment transmission items(order items, order expenses and any additional type) by their orderReference. Items with a transferId are ignored as they are grouped into a different stack
     *
     * @return array<string, array<string, \Generated\Shared\Transfer\PaymentTransmissionItemTransfer>>
     */
    protected function getPaymentTransmissionItemsGroupedByOrderReferenceAndItemReference(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): array {
        $paymentTransmissionItemsGroupedByOrderReference = [];

        foreach ($paymentTransmissionsRequestTransfer->getPaymentTransmissionItems() as $paymentTransmissionItem) {
            if ($paymentTransmissionItem->getTransferId() !== null) {
                continue;
            }

            if (!isset($paymentTransmissionItemsGroupedByOrderReference[$paymentTransmissionItem->getOrderReference()])) {
                $paymentTransmissionItemsGroupedByOrderReference[$paymentTransmissionItem->getOrderReference()] = [];
            }

            $paymentTransmissionItemsGroupedByOrderReference[$paymentTransmissionItem->getOrderReference()][$paymentTransmissionItem->getItemReference()] = $paymentTransmissionItem;
        }

        return $paymentTransmissionItemsGroupedByOrderReference;
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

            foreach ($paymentTransmission->getPaymentTransmissionItems() as $orderItemTransfer) {
                $totalAmount += $orderItemTransfer->getAmount();
                $itemReferences[] = $orderItemTransfer->getItemReference();
            }

            $paymentTransmission->setAmount($totalAmount);
            $paymentTransmission->setItemReferences($itemReferences);
        }

        return $paymentTransmissionsRequestTransfer;
    }
}
