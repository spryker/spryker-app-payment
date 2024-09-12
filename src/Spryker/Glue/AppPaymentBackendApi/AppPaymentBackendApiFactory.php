<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi;

use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToAppPaymentFacadeInterface;
use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToTranslatorFacadeInterface;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapperInterface;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueResponsePaymentMapper;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueResponsePaymentMapperInterface;
use Spryker\Glue\Kernel\Backend\AbstractFactory;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiConfig getConfig()
 */
class AppPaymentBackendApiFactory extends AbstractFactory
{
    public function createGlueRequestPaymentMapper(): GlueRequestPaymentMapperInterface
    {
        return new GlueRequestPaymentMapper();
    }

    public function createGlueResponsePaymentMapper(): GlueResponsePaymentMapperInterface
    {
        return new GlueResponsePaymentMapper();
    }

    public function getAppPaymentFacade(): AppPaymentBackendApiToAppPaymentFacadeInterface
    {
        /** @phpstan-var \Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToAppPaymentFacadeInterface */
        return $this->getProvidedDependency(AppPaymentBackendApiDependencyProvider::FACADE_APP_PAYMENT);
    }

    public function getTranslatorFacade(): AppPaymentBackendApiToTranslatorFacadeInterface
    {
        /** @phpstan-var \Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToTranslatorFacadeInterface */
        return $this->getProvidedDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR);
    }
}
