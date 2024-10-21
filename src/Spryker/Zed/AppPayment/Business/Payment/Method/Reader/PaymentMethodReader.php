<?php

/**
 * Copyright © 2021-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace App\Zed\AppPayment\Business\Payment\Reader;

use App\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use App\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Generated\Shared\Transfer\PaymentMethodTransfer;

class PaymentMethodReader
{
    public function __construct(protected AppPaymentRepositoryInterface $appPaymentRepository)
    {
    }

    public function getPaymentMethodByTenantIdentifierAndPaymentMethodKey(string $tenantIdentifier, string $paymentMethodKey): PaymentMethodTransfer
    {
        $paymentMethodTransferCollection = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        foreach ($paymentMethodTransferCollection as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() !== $paymentMethodKey) {
                continue;
            }

            return $paymentMethodTransfer;
        }

        throw new PaymentMethodNotFoundException(sprintf('Payment method "%s" not found for Tenant "%s"', $paymentMethodKey, $tenantIdentifier));
    }
}
