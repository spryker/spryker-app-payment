<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Redirect;

use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\RedirectRequestTransfer;
use Generated\Shared\Transfer\RedirectResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Throwable;

class Redirect
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function getRedirectUrl(RedirectRequestTransfer $redirectRequestTransfer): RedirectResponseTransfer
    {
        $paymentTransfer = $this->appPaymentRepository->getPaymentByTransactionId($redirectRequestTransfer->getTransactionIdOrFail());
        $appConfigTransfer = $this->appConfigLoader->loadAppConfig($paymentTransfer->getTenantIdentifierOrFail());
        $paymentStatusRequestTransfer = new PaymentStatusRequestTransfer();
        $paymentStatusRequestTransfer
            ->setAppConfigOrFail($appConfigTransfer)
            ->setPayment($paymentTransfer)
            ->setTransactionId($redirectRequestTransfer->getTransactionIdOrFail());

        try {
            $paymentStatusResponseTransfer = $this->appPaymentPlatformPlugin->getPaymentStatus($paymentStatusRequestTransfer);

            if ($paymentStatusResponseTransfer->getIsSuccessful() === true) {
                return (new RedirectResponseTransfer())->setUrl($paymentTransfer->getRedirectSuccessUrl());
            }

            return (new RedirectResponseTransfer())->setUrl(
                sprintf(
                    '/app-payment?%s=%s&%s=%s',
                    PaymentTransfer::TRANSACTION_ID,
                    $paymentTransfer->getTransactionId(),
                    PaymentTransfer::TENANT_IDENTIFIER,
                    $paymentTransfer->getTenantIdentifier(),
                ),
            );
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ]);

            return (new RedirectResponseTransfer())->setUrl($paymentTransfer->getRedirectCancelUrl());
        }
    }
}
