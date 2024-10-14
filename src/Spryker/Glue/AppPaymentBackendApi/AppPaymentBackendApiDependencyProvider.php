<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi;

use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToAppPaymentFacadeBridge;
use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToAppPaymentFacadeInterface;
use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToTranslatorFacadeBridge;
use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToTranslatorFacadeInterface;
use Spryker\Glue\Kernel\Backend\AbstractBundleDependencyProvider;
use Spryker\Glue\Kernel\Backend\Container;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiConfig getConfig()
 */
class AppPaymentBackendApiDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_APP_PAYMENT = 'PAYMENT_BACKEND_API:FACADE_PAYMENT';

    /**
     * @var string
     */
    public const FACADE_TRANSLATOR = 'FACADE_TRANSLATOR';

    public function provideBackendDependencies(Container $container): Container
    {
        $container = parent::provideBackendDependencies($container);
        $container = $this->addAppPaymentFacade($container);
        $container = $this->addTranslatorFacade($container);

        return $container;
    }

    protected function addAppPaymentFacade(Container $container): Container
    {
        $container->set(static::FACADE_APP_PAYMENT, static function (Container $container): AppPaymentBackendApiToAppPaymentFacadeInterface {
            // The PaymentFacade will always be mocked
            // @codeCoverageIgnoreStart
            return new AppPaymentBackendApiToAppPaymentFacadeBridge($container->getLocator()->appPayment()->facade());
            // @codeCoverageIgnoreEnd
        });

        return $container;
    }

    protected function addTranslatorFacade(Container $container): Container
    {
        $container->set(static::FACADE_TRANSLATOR, static function (Container $container): AppPaymentBackendApiToTranslatorFacadeInterface {
            return new AppPaymentBackendApiToTranslatorFacadeBridge($container->getLocator()->translator()->facade());
        });

        return $container;
    }
}
