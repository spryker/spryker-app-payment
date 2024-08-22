<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\AppConfig;

use Generated\Shared\Transfer\AppConfigCriteriaTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;

class AppConfigLoader
{
    public function __construct(protected AppPaymentToAppKernelFacadeInterface $appPaymentToAppKernelFacade)
    {
    }

    public function loadAppConfig(string $tenantIdentifier): AppConfigTransfer
    {
        return $this->appPaymentToAppKernelFacade->getConfig(
            (new AppConfigCriteriaTransfer())->setTenantIdentifier($tenantIdentifier),
        );
    }
}
