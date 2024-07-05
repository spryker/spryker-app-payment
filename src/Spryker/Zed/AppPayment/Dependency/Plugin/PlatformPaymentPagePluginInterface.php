<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;

interface PlatformPaymentPagePluginInterface extends PlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `PaymentPageRequestTransfer` with:
     *   - `PaymentPageRequestTransfer::transactionId`
     *   - `PaymentPageRequestTransfer::appConfig` (AppConfigTransfer)
     *   - `PaymentPageRequestTransfer::appConfig::config`
     *   - `PaymentPageRequestTransfer::payment` (PaymentTransfer)
     * - Returns a `PaymentPageResponseTransfer`.
     * - Requires `PaymentPageResponseTransfer::isSuccessful`to be set.
     * - Requires `PaymentPageResponseTransfer::paymentPageTemplate`to be set.
     * - Requires `PaymentPageResponseTransfer::paymentPageData`to be set.
     * - Returns a `PaymentPageResponseTransfer` with a failed response when the 3rd party provider could not process the request.
     * - Returns a `PaymentPageResponseTransfer` with a successful response when the 3rd party provider was able to process the request.
     *
     * @api
     */
    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer;
}
