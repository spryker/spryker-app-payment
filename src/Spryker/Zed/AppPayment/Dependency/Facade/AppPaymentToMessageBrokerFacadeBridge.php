<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Facade;

use Generated\Shared\Transfer\MessageResponseTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

class AppPaymentToMessageBrokerFacadeBridge implements AppPaymentToMessageBrokerFacadeInterface
{
    /**
     * @var \Spryker\Zed\MessageBroker\Business\MessageBrokerFacadeInterface
     */
    protected $messageBrokerFacade;

    /**
     * @param \Spryker\Zed\MessageBroker\Business\MessageBrokerFacadeInterface $messageBrokerFacade
     */
    public function __construct($messageBrokerFacade)
    {
        $this->messageBrokerFacade = $messageBrokerFacade;
    }

    public function sendMessage(TransferInterface $messageTransfer): MessageResponseTransfer
    {
        return $this->messageBrokerFacade->sendMessage($messageTransfer);
    }
}
