<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication;

use Spryker\Glue\AppPaymentBackendApi\Controller\CancelPreOrderPaymentResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\ConfirmPreOrderPaymentResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\CustomerResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\InitializePaymentResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\PaymentsTransfersResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\PreOrderPaymentResourceController;
use Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RouteProviderPluginInterface;
use Spryker\Glue\Kernel\Backend\AbstractPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @codeCoverageIgnore This class will only be used when caching is disabled. Without this Plugin the InitializePayment request would fail and we would see an issue right away.
 */
class AppPaymentBackendApiRouteProviderPlugin extends AbstractPlugin implements RouteProviderPluginInterface
{
    /**
     * @var string
     */
    public const ROUTE_INITIALIZE_PAYMENT = '/private/initialize-payment';

    /**
     * @var string
     */
    public const ROUTE_PRE_ORDER_PAYMENT = '/private/pre-order-payment';

    /**
     * @var string
     */
    public const ROUTE_CONFIRM_PRE_ORDER_PAYMENT = '/private/confirm-pre-order-payment';

    /**
     * @var string
     */
    public const ROUTE_CANCEL_PRE_ORDER_PAYMENT = '/private/cancel-pre-order-payment';

    /**
     * @var string
     */
    public const ROUTE_PAYMENTS_TRANSFERS = '/private/payments/transfers';

    /**
     * @var string
     */
    public const ROUTE_CUSTOMER = '/private/customer';

    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection->add('postPayment', $this->getPostPaymentRoute());
        $routeCollection->add('postPreOrderPayment', $this->getPostPreOrderPaymentRoute());
        $routeCollection->add('postConfirmPreOrderPayment', $this->getPostConfirmPreOrderPaymentRoute());
        $routeCollection->add('postCancelPreOrderPayment', $this->getPostCancelPreOrderPaymentRoute());
        $routeCollection->add('postPaymentsTransfers', $this->getPostPaymentsTransfersRoute());
        $routeCollection->add('postCustomer', $this->getPostCustomerRoute());

        return $routeCollection;
    }

    public function getPostPaymentRoute(): Route
    {
        return (new Route(static::ROUTE_INITIALIZE_PAYMENT))
            ->setDefaults([
                '_controller' => [InitializePaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostPreOrderPaymentRoute(): Route
    {
        return (new Route(static::ROUTE_PRE_ORDER_PAYMENT))
            ->setDefaults([
                '_controller' => [PreOrderPaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostConfirmPreOrderPaymentRoute(): Route
    {
        return (new Route(static::ROUTE_CONFIRM_PRE_ORDER_PAYMENT))
            ->setDefaults([
                '_controller' => [ConfirmPreOrderPaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostCancelPreOrderPaymentRoute(): Route
    {
        return (new Route(static::ROUTE_CANCEL_PRE_ORDER_PAYMENT))
            ->setDefaults([
                '_controller' => [CancelPreOrderPaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostPaymentsTransfersRoute(): Route
    {
        return (new Route(static::ROUTE_PAYMENTS_TRANSFERS))
            ->setDefaults([
                '_controller' => [PaymentsTransfersResourceController::class, 'postAction'],
                '_resourceName' => 'payments-transfers',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostCustomerRoute(): Route
    {
        return (new Route(static::ROUTE_CUSTOMER))
            ->setDefaults([
                '_controller' => [CustomerResourceController::class, 'postAction'],
                '_resourceName' => 'customer',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }
}
