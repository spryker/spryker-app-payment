<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\Testify\Helper;

use Codeception\Lib\Framework;
use Codeception\TestInterface;
use Spryker\Glue\AppKernel\AppKernelDependencyProvider;
use Spryker\Glue\AppPaymentBackendApi\Plugin\AppKernel\PaymentConfigurationValidatorPlugin;
use Spryker\Shared\ErrorHandler\ErrorHandlerConstants;
use Spryker\Shared\Http\Plugin\EventDispatcher\ResponseListenerEventDispatcherPlugin;
use Spryker\Shared\Twig\Plugin\DebugTwigPlugin;
use Spryker\Shared\Twig\Plugin\RoutingTwigPlugin;
use Spryker\Zed\Application\ApplicationDependencyProvider;
use Spryker\Zed\Application\Communication\Bootstrap\BackofficeBootstrap;
use Spryker\Zed\Application\Communication\Plugin\EventDispatcher\HeadersSecurityEventDispatcherPlugin;
use Spryker\Zed\Application\Communication\Plugin\Twig\ApplicationTwigPlugin;
use Spryker\Zed\ErrorHandler\Communication\Plugin\Application\ErrorHandlerApplicationPlugin;
use Spryker\Zed\ErrorHandler\Communication\Plugin\EventDispatcher\ErrorPageEventDispatcherPlugin;
use Spryker\Zed\EventDispatcher\Communication\Plugin\Application\BackofficeEventDispatcherApplicationPlugin;
use Spryker\Zed\EventDispatcher\EventDispatcherDependencyProvider;
use Spryker\Zed\Gui\Communication\Plugin\Twig\AssetsPathTwigPlugin;
use Spryker\Zed\Http\Communication\Plugin\Application\HttpApplicationPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\CacheControlHeaderEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\CookieEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\FragmentEventDispatcherPlugin;
use Spryker\Zed\Http\Communication\Plugin\EventDispatcher\HstsHeaderEventDispatcher;
use Spryker\Zed\Http\Communication\Plugin\Twig\HttpKernelTwigPlugin;
use Spryker\Zed\Kernel\Communication\Plugin\AutoloaderCacheEventDispatcherPlugin;
use Spryker\Zed\Kernel\Communication\Plugin\EventDispatcher\RedirectUrlValidationEventDispatcherPlugin;
use Spryker\Zed\Propel\Communication\Plugin\Application\PropelApplicationPlugin;
use Spryker\Zed\Router\Communication\Plugin\Application\BackofficeRouterApplicationPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RequestAttributesEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RouterListenerEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\EventDispatcher\RouterLocaleEventDispatcherPlugin;
use Spryker\Zed\Router\Communication\Plugin\Router\BackofficeRouterPlugin;
use Spryker\Zed\Router\RouterDependencyProvider;
use Spryker\Zed\Session\Communication\Plugin\Application\SessionApplicationPlugin;
use Spryker\Zed\Session\Communication\Plugin\EventDispatcher\SaveSessionEventDispatcherPlugin;
use Spryker\Zed\Session\Communication\Plugin\EventDispatcher\SessionEventDispatcherPlugin;
use Spryker\Zed\Store\Communication\Plugin\Application\BackofficeStoreApplicationPlugin;
use Spryker\Zed\Translator\Communication\Plugin\Twig\TranslatorTwigPlugin;
use Spryker\Zed\Twig\Communication\Plugin\Application\TwigApplicationPlugin;
use Spryker\Zed\Twig\Communication\Plugin\EventDispatcher\TwigEventDispatcherPlugin;
use Spryker\Zed\Twig\Communication\Plugin\FilesystemTwigLoaderPlugin;
use Spryker\Zed\Twig\TwigDependencyProvider;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Zed\Testify\Helper\Communication\DependencyProviderHelperTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

class PaymentBootstrapHelper extends Framework
{
    use ConfigHelperTrait;
    use DependencyProviderHelperTrait;

    public function _before(TestInterface $test): void
    {
        $this->disableWhoopsErrorHandler();

        $requestFactory = static function (array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): Request {
            $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);
            $request->server->set('SERVER_NAME', 'localhost');

            return $request;
        };

        Request::setFactory($requestFactory);

        $this->getDependencyProviderHelper()->setDependency(ApplicationDependencyProvider::PLUGINS_BACKOFFICE_APPLICATION, [
            new TwigApplicationPlugin(),
            new PropelApplicationPlugin(),
            new BackofficeRouterApplicationPlugin(),
            new HttpApplicationPlugin(),
            new ErrorHandlerApplicationPlugin(),
            new BackofficeStoreApplicationPlugin(),
            new BackofficeEventDispatcherApplicationPlugin(),
            new SessionApplicationPlugin(),
        ]);

        $this->getDependencyProviderHelper()->setDependency(RouterDependencyProvider::PLUGINS_BACKOFFICE_ROUTER, [
            new BackofficeRouterPlugin(),
        ]);

        $this->getDependencyProviderHelper()->setDependency(AppKernelDependencyProvider::PLUGINS_REQUEST_CONFIGURE_VALIDATOR, [
            new PaymentConfigurationValidatorPlugin(),
        ]);

        $this->getDependencyProviderHelper()->setDependency(TwigDependencyProvider::PLUGINS_TWIG, [
            new DebugTwigPlugin(),
            new HttpKernelTwigPlugin(),
            new RoutingTwigPlugin(),
            new ApplicationTwigPlugin(),
            new TranslatorTwigPlugin(),
            new AssetsPathTwigPlugin(),
        ]);

        $this->getDependencyProviderHelper()->setDependency(TwigDependencyProvider::PLUGINS_TWIG_LOADER, [
            new FilesystemTwigLoaderPlugin(),
        ]);

        $this->getDependencyProviderHelper()->setDependency(EventDispatcherDependencyProvider::PLUGINS_BACKOFFICE_EVENT_DISPATCHER, [
            new HeadersSecurityEventDispatcherPlugin(),
            new RouterLocaleEventDispatcherPlugin(),
            new RouterListenerEventDispatcherPlugin(),
            new CookieEventDispatcherPlugin(),
            new FragmentEventDispatcherPlugin(),
            new HstsHeaderEventDispatcher(),
            new CacheControlHeaderEventDispatcherPlugin(),
            new TwigEventDispatcherPlugin(),
            new SessionEventDispatcherPlugin(),
            new SaveSessionEventDispatcherPlugin(),
            new AutoloaderCacheEventDispatcherPlugin(),
            new RequestAttributesEventDispatcherPlugin(),
            new ResponseListenerEventDispatcherPlugin(),
            new ErrorPageEventDispatcherPlugin(),
            new RedirectUrlValidationEventDispatcherPlugin(),
        ]);

        $backofficeBootstrap = new BackofficeBootstrap();
        $this->client = new HttpKernelBrowser($backofficeBootstrap->boot());
    }

    public function seeRedirectUrlEquals(string $expectedUrl): void
    {
        $response = $this->client->getResponse();

        if ($response instanceof RedirectResponse) {
            $this->assertSame($expectedUrl, $response->getTargetUrl());

            return;
        }

        $this->fail('Response is not a redirect response.');
    }

    /**
     * The WhoopsErrorHandler converts E_USER_DEPRECATED into exception, we need to disable it for controller tests.
     */
    protected function disableWhoopsErrorHandler(): void
    {
        $this->getConfigHelper()->setConfig(ErrorHandlerConstants::IS_PRETTY_ERROR_HANDLER_ENABLED, false);
    }
}
