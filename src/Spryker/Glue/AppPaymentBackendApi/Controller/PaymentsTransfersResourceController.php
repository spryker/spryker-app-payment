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
        $paymentTransmissionsRequestTransfer = $this->getFactory()->createGlueRequestPaymentMapper()->mapGlueRequestTransferToPaymentTransmissionsRequestTransfer($glueRequestTransfer);
        $paymentTransmissionsResponseTransfer = $this->getFactory()->getAppPaymentFacade()->transferPayments($paymentTransmissionsRequestTransfer);

        return $this->getFactory()->createGlueResponsePaymentMapper()->mapPaymentTransmissionsResponseTransferToSingleResourceGlueResponseTransfer($paymentTransmissionsResponseTransfer);
    }
}
