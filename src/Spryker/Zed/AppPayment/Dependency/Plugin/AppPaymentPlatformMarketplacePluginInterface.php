<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;

interface AppPaymentPlatformMarketplacePluginInterface extends AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Transfers payments.
     * - Requires `PaymentTransmissionsRequestTransfer::transactionId`to be set.
     * - Requires `PaymentTransmissionsRequestTransfer::appConfig`to be set.
     * - Returns a `PaymentTransmissionsResponseTransfer`.
     * - Requires `PaymentTransmissionsResponseTransfer::isSuccessful`to be set.
     * - Requires `PaymentTransmissionsResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Requires `PaymentTransmissionsResponseTransfer::paymentTransmissions` to be set.
     * - Returns a `PaymentTransmissionsResponseTransfer` with a failed response status and message when the 3rd party provider could not process the request.
     *
     * @api
     */
    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer;
}
