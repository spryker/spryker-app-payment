<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier;

use Spryker\Shared\Kernel\Transfer\AbstractTransfer;

class TenantIdentifierExtractor
{
    public function getTenantIdentifierFromMessage(AbstractTransfer $messageTransfer): string
    {
        if ($messageTransfer->getMessageAttributesOrFail()->getTenantIdentifier() !== null) {
            return $messageTransfer->getMessageAttributesOrFail()->getTenantIdentifier();
        }

        // Fallback for old systems that are sending the store reference as tenant identifier.
        return $messageTransfer->getMessageAttributesOrFail()->getStoreReference();
    }
}
