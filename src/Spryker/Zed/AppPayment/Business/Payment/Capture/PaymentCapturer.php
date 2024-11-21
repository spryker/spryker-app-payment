<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Capture;

use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentCapturer
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function capturePayment(CapturePaymentRequestTransfer $capturePaymentRequestTransfer): CapturePaymentResponseTransfer
    {
        try {
            $capturePaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($capturePaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail()));
            $capturePaymentResponseTransfer = $this->appPaymentPlatformPlugin->capturePayment($capturePaymentRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $capturePaymentRequestTransfer->getPaymentOrFail()->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $capturePaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail(),
            ]);
            $capturePaymentResponseTransfer = new CapturePaymentResponseTransfer();
            $capturePaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage())
                ->setStatus(PaymentStatus::STATUS_CAPTURE_FAILED);
        }

        /** @phpstan-var \Generated\Shared\Transfer\CapturePaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($capturePaymentRequestTransfer, $capturePaymentResponseTransfer): \Generated\Shared\Transfer\CapturePaymentResponseTransfer {
            $this->savePayment($capturePaymentRequestTransfer->getPaymentOrFail(), $capturePaymentResponseTransfer->getStatusOrFail());

            return $capturePaymentResponseTransfer;
        });
    }

    protected function savePayment(PaymentTransfer $paymentTransfer, string $status): void
    {
        $paymentTransfer->setStatus($status);
        $this->appPaymentEntityManager->savePayment($paymentTransfer);
    }
}
