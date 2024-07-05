<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Facade;

use Generated\Shared\Transfer\MessageResponseTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

interface AppPaymentToMessageBrokerFacadeInterface
{
    public function sendMessage(TransferInterface $messageTransfer): MessageResponseTransfer;
}
