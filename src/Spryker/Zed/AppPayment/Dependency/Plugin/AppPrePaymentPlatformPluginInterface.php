<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;

interface AppPrePaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `ConfirmPaymentRequestTransfer` with:
     *   - `ConfirmPaymentRequestTransfer::orderReference`
     *   - `ConfirmPaymentRequestTransfer::paymentServiceProviderData`
     * - Returns a `ConfirmPaymentResponseTransfer`.
     * - Requires `ConfirmPaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `ConfirmPaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request or any other issue occurs.
     *
     * @api
     */
    public function confirmPayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer;
}
