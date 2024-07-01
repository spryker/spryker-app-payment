<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Controller;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Spryker\Glue\Kernel\Backend\Controller\AbstractController;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class PaymentsTransfersResourceController extends AbstractController
{
    public function postAction(GlueRequestTransfer $glueRequestTransfer): GlueResponseTransfer
    {
        $paymentsTransmissionsRequestTransfer = $this->getFactory()->createGlueRequestPaymentMapper()->mapGlueRequestTransferToPaymentsTransmissionsRequestTransfer($glueRequestTransfer);
        $paymentsTransmissionsResponseTransfer = $this->getFactory()->getAppPaymentFacade()->transferPayments($paymentsTransmissionsRequestTransfer);

        return $this->getFactory()->createGlueResponsePaymentMapper()->mapPaymentsTransmissionsResponseTransferToSingleResourceGlueResponseTransfer($paymentsTransmissionsResponseTransfer);
    }
}
