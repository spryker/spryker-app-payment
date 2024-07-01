<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Dependency\Facade;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;

interface AppPaymentBackendApiToAppPaymentFacadeInterface
{
    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer;

    public function validatePaymentConfiguration(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer;

    public function transferPayments(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsResponseTransfer;
}
