<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\AppKernel;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Spryker\Zed\AppKernelExtension\Dependency\Plugin\ConfigurationAfterDeletePluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory getFactory()
 */
class DeleteTenantPaymentsConfigurationAfterDeletePlugin extends AbstractPlugin implements ConfigurationAfterDeletePluginInterface
{
    /**
     * {@inheritDoc}
     * - Is provided to remove all payments related to the tenant after the app disconnection, which is not necessary
     *      in production environments to keep data consistency, but can be useful for development purposes.
     * - Validates if the tenant payments deletion after the app disconnection is enabled.
     * - Deletes all the payments related to the tenant after the app disconnection.
     *
     * @api
     */
    public function afterDelete(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        if (!$this->getConfig()->getIsTenantPaymentsDeletionAfterDisconnectionEnabled()) {
            return $appConfigTransfer;
        }

        $paymentCollectionDeleteCriteriaTransfer = (new PaymentCollectionDeleteCriteriaTransfer())
            ->setTenantIdentifier($appConfigTransfer->getTenantIdentifier());

        $this->getFacade()->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);

        return $appConfigTransfer;
    }
}
