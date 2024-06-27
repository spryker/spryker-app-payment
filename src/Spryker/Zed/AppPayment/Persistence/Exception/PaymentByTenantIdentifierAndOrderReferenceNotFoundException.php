<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence\Exception;

use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;

class PaymentByTenantIdentifierAndOrderReferenceNotFoundException extends PaymentException
{
    public function __construct(string $tenantIdentifier, string $orderReference)
    {
        parent::__construct(MessageBuilder::paymentByTenantIdentifierAndOrderReferenceNotFound($tenantIdentifier, $orderReference));
    }
}
