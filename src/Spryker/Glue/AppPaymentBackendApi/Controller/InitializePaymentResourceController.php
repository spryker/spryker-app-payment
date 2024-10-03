<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Controller;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Spryker\Glue\Kernel\Backend\Controller\AbstractController;
use Spryker\Shared\Log\LoggerTrait;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class InitializePaymentResourceController extends AbstractController
{
    use LoggerTrait;

    public function postAction(GlueRequestTransfer $glueRequestTransfer): GlueResponseTransfer
    {
        $initializePaymentRequestTransfer = $this->getFactory()->createGlueRequestPaymentMapper()->mapGlueRequestTransferToInitializePaymentRequestTransfer($glueRequestTransfer);
        $initializePaymentResponseTransfer = $this->getFactory()->getAppPaymentFacade()->initializePayment($initializePaymentRequestTransfer);
        $this->getFactory()->createGlueResponsePaymentMapper()->mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer($initializePaymentResponseTransfer);
        $this->getLogger()->info('initializePaymentResponse', $initializePaymentResponseTransfer->toArray());

        return $this->getFactory()->createGlueResponsePaymentMapper()->mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer($initializePaymentResponseTransfer);
    }
}
