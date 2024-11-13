<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method\Reader;

use Generated\Shared\Transfer\PaymentMethodCriteriaTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Spryker\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer\PaymentMethodNormalizer;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethodReader
{
    public function __construct(protected AppPaymentRepositoryInterface $appPaymentRepository, protected PaymentMethodNormalizer $paymentMethodNormalizer)
    {
    }

    public function getPaymentMethodByTenantIdentifierAndPaymentMethodKey(PaymentMethodCriteriaTransfer $paymentMethodCriteriaTransfer): PaymentMethodTransfer
    {
        if ($paymentMethodCriteriaTransfer->getTenantIdentifier() === null || $paymentMethodCriteriaTransfer->getTenantIdentifier() === '' || $paymentMethodCriteriaTransfer->getTenantIdentifier() === '0' || ($paymentMethodCriteriaTransfer->getPaymentMethodKey() === null || $paymentMethodCriteriaTransfer->getPaymentMethodKey() === '' || $paymentMethodCriteriaTransfer->getPaymentMethodKey() === '0')) {
            throw new PaymentMethodNotFoundException(sprintf(
                'Payment method "%s" not found for Tenant "%s". Maybe the TenantIdentifier, the PaymentMethodKey, or both are missing.',
                $paymentMethodCriteriaTransfer->getPaymentMethodKey(),
                $paymentMethodCriteriaTransfer->getTenantIdentifier(),
            ));
        }

        $paymentMethodTransferCollection = $this->appPaymentRepository->getTenantPaymentMethods($paymentMethodCriteriaTransfer->getTenantIdentifier());

        $normalizePaymentMethodKey = $this->paymentMethodNormalizer->normalizePaymentMethodKey($paymentMethodCriteriaTransfer->getPaymentMethodKey());

        foreach ($paymentMethodTransferCollection as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() !== $normalizePaymentMethodKey) {
                continue;
            }

            return $paymentMethodTransfer;
        }

        throw new PaymentMethodNotFoundException(sprintf(
            'Payment method "%s" not found for Tenant "%s"',
            $paymentMethodCriteriaTransfer->getPaymentMethodKey(),
            $paymentMethodCriteriaTransfer->getTenantIdentifier(),
        ));
    }
}
