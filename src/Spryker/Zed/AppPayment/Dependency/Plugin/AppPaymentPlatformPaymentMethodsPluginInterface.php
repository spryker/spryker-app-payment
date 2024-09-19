<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\PaymentMethodConfigurationRequestTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;

interface AppPaymentPlatformPaymentMethodsPluginInterface extends AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `PaymentMethodConfigurationRequestTransfer` with:
     *   - `appConfig`
     * - Returns a `PaymentMethodConfigurationRequestTransfer` with.
     *   - `paymentMethods` filled with the payment methods available for the app.
     *   - Each `PaymentMethodTransfer` contains the
     *      - `name` which is the name of the Payment Method e.g. PayPal.
     *      - `providerName` which is the name of the Payment Service Provider e.g. PayOne.
     *      - `paymentMethodAppConfiguration` with the configuration for the app.
     *
     * @api
     */
    public function configurePaymentMethods(
        PaymentMethodConfigurationRequestTransfer $paymentMethodConfigurationRequestTransfer
    ): PaymentMethodConfigurationResponseTransfer;
}
