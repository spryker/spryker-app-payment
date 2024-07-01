<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Initialize\PaymentInitializer;
use Spryker\Zed\AppPayment\Business\Payment\Page\PaymentPage;
use Spryker\Zed\AppPayment\Business\Payment\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Validate\ConfigurationValidator;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookHandler;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;

class Payment
{
    public function __construct(
        protected PlatformPluginInterface $platformPlugin,
        protected ConfigurationValidator $configurationValidator,
        protected PaymentInitializer $paymentInitializer,
        protected PaymentTransfer $paymentTransfer,
        protected PaymentPage $paymentPage,
        protected WebhookHandler $webhookHandler
    ) {
    }

    public function validateConfiguration(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        return $this->configurationValidator->validatePaymentConfiguration($glueRequestTransfer);
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        return $this->paymentInitializer->initializePayment($initializePaymentRequestTransfer);
    }

    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
    {
        return $this->paymentPage->getPaymentPage($paymentPageRequestTransfer);
    }

    public function transferPayments(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsResponseTransfer
    {
        return $this->paymentTransfer->transferPayments($paymentsTransmissionsRequestTransfer);
    }
}
