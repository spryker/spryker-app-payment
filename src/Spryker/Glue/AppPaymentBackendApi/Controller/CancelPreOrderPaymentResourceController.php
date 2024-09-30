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
class CancelPreOrderPaymentResourceController extends AbstractController
{
    public function postAction(GlueRequestTransfer $glueRequestTransfer): GlueResponseTransfer
    {
        $cancelPreOrderPaymentRequestTransfer = $this->getFactory()->createGlueRequestPaymentMapper()->mapGlueRequestTransferToCancelPreOrderPaymentRequestTransfer($glueRequestTransfer);
        $cancelPreOrderPaymentResponseTransfer = $this->getFactory()->getAppPaymentFacade()->cancelPreOrderPayment($cancelPreOrderPaymentRequestTransfer);

        return $this->getFactory()->createGlueResponsePaymentMapper()->mapCancelPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer($cancelPreOrderPaymentResponseTransfer);
    }
}
