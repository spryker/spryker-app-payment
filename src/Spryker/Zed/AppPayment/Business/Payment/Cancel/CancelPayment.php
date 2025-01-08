<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Cancel;

use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatusTransitionValidator;
use Spryker\Zed\AppPayment\Business\Payment\Writer\PaymentWriterInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class CancelPayment
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected PaymentStatusTransitionValidator $paymentStatusTransitionValidator,
        protected PaymentWriterInterface $paymentWriter,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function cancelPayment(CancelPaymentRequestTransfer $cancelPaymentRequestTransfer): CancelPaymentResponseTransfer
    {
        if (!$this->paymentStatusTransitionValidator->isTransitionAllowed($cancelPaymentRequestTransfer->getPaymentOrFail()->getStatusOrFail(), PaymentStatus::STATUS_CANCELED)) {
            return (new CancelPaymentResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessage('Payment status transition is not allowed')
                ->setStatus(PaymentStatus::STATUS_CANCELLATION_FAILED);
        }

        try {
            $cancelPaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($cancelPaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail()));
            $cancelPaymentResponseTransfer = $this->appPaymentPlatformPlugin->cancelPayment($cancelPaymentRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                $throwable->getMessage(),
                [
                    CancelPaymentRequestTransfer::TRANSACTION_ID => $cancelPaymentRequestTransfer->getTransactionIdOrFail(),
                    PaymentTransfer::TENANT_IDENTIFIER => $cancelPaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail(),
                ],
            );
            $cancelPaymentResponseTransfer = new CancelPaymentResponseTransfer();
            $cancelPaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage())
                ->setStatus(PaymentStatus::STATUS_CANCELLATION_FAILED);
        }

        /** @phpstan-var \Generated\Shared\Transfer\CancelPaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($cancelPaymentRequestTransfer, $cancelPaymentResponseTransfer): \Generated\Shared\Transfer\CancelPaymentResponseTransfer {
            $this->savePayment($cancelPaymentRequestTransfer->getPaymentOrFail(), $cancelPaymentResponseTransfer->getStatusOrFail());

            return $cancelPaymentResponseTransfer;
        });
    }

    protected function savePayment(PaymentTransfer $paymentTransfer, string $status): void
    {
        $paymentTransfer->setStatus($status);
        $this->paymentWriter->updatePayment($paymentTransfer);
    }
}
