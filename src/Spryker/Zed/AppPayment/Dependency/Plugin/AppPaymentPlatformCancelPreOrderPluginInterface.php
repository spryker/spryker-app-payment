<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\CancelPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;

interface AppPaymentPlatformCancelPreOrderPluginInterface extends AppPaymentPlatformPluginInterface
{
 /**
  * Specification:
  * - Receives a `CancelPreOrderPaymentRequestTransfer` with:
  *   - `appConfig`
  *   - `payment`
  *   - `paymentServiceProviderData`
  * - Returns a `CancelPreOrderPaymentResponseTransfer`.
  * - Requires `CancelPreOrderPaymentResponseTransfer::isSuccessful`to be set.
  * - Requires `CancelPreOrderPaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request or any other issue occurs.
  *
  * @api
  */
    public function cancelPreOrderPayment(
        CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer
    ): CancelPreOrderPaymentResponseTransfer;
}
