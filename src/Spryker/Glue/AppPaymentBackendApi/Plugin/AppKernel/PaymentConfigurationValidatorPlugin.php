<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spryker\Glue\AppPaymentBackendApi\Plugin\AppKernel;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RequestValidatorPluginInterface;
use Spryker\Glue\Kernel\AbstractPlugin;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class PaymentConfigurationValidatorPlugin extends AbstractPlugin implements RequestValidatorPluginInterface
{
    public function validate(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        return $this->getFactory()->getAppPaymentFacade()->validatePaymentConfiguration($glueRequestTransfer);
    }
}
