<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\MessageBrokerAws;

use Generated\Shared\Transfer\HttpRequestTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MessageBrokerAwsExtension\Dependency\Plugin\HttpChannelMessageReceiverRequestExpanderPluginInterface;

/**
 * @codeCoverageIgnore This will never be called in testing environment.
 *
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 */
class ConsumerIdHttpChannelMessageReceiverRequestExpanderPlugin extends AbstractPlugin implements HttpChannelMessageReceiverRequestExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Sets the `Consumer-Id` header using the value from the module's configuration.
     *
     * @api
     */
    public function expand(HttpRequestTransfer $httpRequestTransfer): HttpRequestTransfer
    {
        return $httpRequestTransfer->addHeader('Consumer-Id', $this->getConfig()->getAppIdentifier());
    }
}
