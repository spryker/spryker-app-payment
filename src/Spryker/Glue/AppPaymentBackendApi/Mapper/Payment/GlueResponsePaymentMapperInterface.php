<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use Generated\Shared\Transfer\GlueResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;

interface GlueResponsePaymentMapperInterface
{
    public function mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): GlueResponseTransfer;
}
