<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use Generated\Shared\Transfer\GlueResourceTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Symfony\Component\HttpFoundation\Response;

class GlueResponsePaymentMapper implements GlueResponsePaymentMapperInterface
{
    public function mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();

        return $this->addInitializePaymentResponseTransferToGlueResponse($initializePaymentResponseTransfer, $glueResponseTransfer);
    }

    public function addInitializePaymentResponseTransferToGlueResponse(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer,
        GlueResponseTransfer $glueResponseTransfer
    ): GlueResponseTransfer {
        $glueResourceTransfer = new GlueResourceTransfer();
        $glueResourceTransfer->setAttributes($initializePaymentResponseTransfer);
        $glueResourceTransfer->setType('payment');

        $glueResponseTransfer->setContent(
            (string)json_encode($initializePaymentResponseTransfer->toArray()),
        );

        $glueResponseTransfer->addResource($glueResourceTransfer);
        // SCOS Checkout expects a 200 response code and check isSuccessful property to show error message.
        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);

        return $glueResponseTransfer;
    }
}
