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

class AppPaymentBackendApiToAppPaymentFacadeBridge implements AppPaymentBackendApiToAppPaymentFacadeInterface
{
    /**
     * @var \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface
     */
    protected $appPaymentFacade;

    /**
     * @param \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface $appPaymentFacade
     */
    public function __construct($appPaymentFacade)
    {
        $this->appPaymentFacade = $appPaymentFacade;
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        return $this->appPaymentFacade->initializePayment($initializePaymentRequestTransfer);
    }

    public function validatePaymentConfiguration(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        return $this->appPaymentFacade->validatePaymentConfiguration($glueRequestTransfer);
    }

    public function transferPayments(PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer): PaymentsTransmissionsResponseTransfer
    {
        return $this->appPaymentFacade->transferPayments($paymentsTransmissionsRequestTransfer);
    }
}
