<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method\Reader;

use Generated\Shared\Transfer\PaymentMethodTransfer;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\SeparatorToDash;
use Spryker\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethodReader
{
    public function __construct(protected AppPaymentRepositoryInterface $appPaymentRepository)
    {
    }

    public function getPaymentMethodByTenantIdentifierAndPaymentMethodKey(string $tenantIdentifier, string $paymentMethodKey): PaymentMethodTransfer
    {
        $paymentMethodTransferCollection = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        $filteredPaymentMethodKey = $this->getFilteredPaymentMethodKey($paymentMethodKey);

        foreach ($paymentMethodTransferCollection as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() !== $filteredPaymentMethodKey) {
                continue;
            }

            return $paymentMethodTransfer;
        }

        throw new PaymentMethodNotFoundException(sprintf('Payment method "%s" not found for Tenant "%s"', $paymentMethodKey, $tenantIdentifier));
    }

    protected function getFilteredPaymentMethodKey(string $paymentMethodKey): string
    {
        $filterChain = new FilterChain();
        $filterChain
            ->attach(new StringToLower())
            ->attach(new SeparatorToDash());

        return $filterChain->filter($paymentMethodKey);
    }
}
