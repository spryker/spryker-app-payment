<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method\Reader;

use Generated\Shared\Transfer\PaymentMethodTransfer;
use Spryker\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer\PaymentMethodNormalizer;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethodReader
{
    public function __construct(protected AppPaymentRepositoryInterface $appPaymentRepository, protected PaymentMethodNormalizer $paymentMethodNormalizer)
    {
    }

    public function getPaymentMethodByTenantIdentifierAndPaymentMethodKey(string $tenantIdentifier, string $paymentMethodKey): PaymentMethodTransfer
    {
        $paymentMethodTransferCollection = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        $normalizePaymentMethodKey = $this->paymentMethodNormalizer->normalizePaymentMethodKey($paymentMethodKey);

        foreach ($paymentMethodTransferCollection as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() !== $normalizePaymentMethodKey) {
                continue;
            }

            return $paymentMethodTransfer;
        }

        throw new PaymentMethodNotFoundException(sprintf('Payment method "%s" not found for Tenant "%s"', $paymentMethodKey, $tenantIdentifier));
    }
}
