<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use GuzzleHttp\RequestOptions;

class GlueRequestPaymentMapper implements GlueRequestPaymentMapperInterface
{
 /**
  * @var string
  */
    public const HEADER_TENANT_IDENTIFIER = 'x-tenant-identifier';

    public function mapGlueRequestTransferToInitializePaymentRequestTransfer(
        GlueRequestTransfer $glueRequestTransfer
    ): InitializePaymentRequestTransfer {
        $metaData = $glueRequestTransfer->getMeta();

        $initializePaymentRequestTransfer = new InitializePaymentRequestTransfer();
        $initializePaymentRequestTransfer->fromArray($glueRequestTransfer->getAttributes()[RequestOptions::FORM_PARAMS] ?? $glueRequestTransfer->getAttributes(), true);
        $initializePaymentRequestTransfer->setTenantIdentifier($metaData[static::HEADER_TENANT_IDENTIFIER][0] ?? ($metaData['x-store-reference'][0] ?? ''));

        return $initializePaymentRequestTransfer;
    }
}
