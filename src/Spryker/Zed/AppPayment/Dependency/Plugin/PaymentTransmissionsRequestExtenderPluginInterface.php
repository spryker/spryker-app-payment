<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;

interface PaymentTransmissionsRequestExtenderPluginInterface
{
    /**
     * @api
     */
    public function extendPaymentTransmissionsRequest(
        PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer
    ): PaymentTransmissionsRequestTransfer;
}
