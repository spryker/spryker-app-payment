<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppConfig;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\AppConfigValidateResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValidationErrorTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Orm\Zed\AppKernel\Persistence\SpyAppConfigQuery;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group AppConfig
 * @group AppConfigTest
 * Add your own group annotations below this line
 */
class AppConfigTest extends Unit
{
    use DependencyHelperTrait;
    use DataCleanupHelperTrait;

    protected AppConfigTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->getDependencyHelper()->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->getDependencyHelper()->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, []);

        $this->getDependencyHelper()->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);
    }

    public function testReceivingConfigurationFromAppStoreCatalogWithoutTheAcceptLanguageHeaderAppliesDefaultLocaleEnUSAndSavesAppConfigurationWhenPlatformValidationWasSuccessful(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($tenantIdentifier): void {
            SpyAppConfigQuery::create()
                ->filterByTenantIdentifier($tenantIdentifier)
                ->delete();
        });

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer->setIsSuccessful(true);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/configure', $this->tester->getAppConfigureRequestData());

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);
    }

    public function testReceivingConfigurationFromAppStoreCatalogSavesAppConfigurationWhenPlatformValidationWasSuccessful(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($tenantIdentifier): void {
            SpyAppConfigQuery::create()
                ->filterByTenantIdentifier($tenantIdentifier)
                ->delete();
        });

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer->setIsSuccessful(true);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/configure', $this->tester->getAppConfigureRequestData());

        // Assert
        $this->assertSame(200, $response->getStatusCode());
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);
    }

    public function testReceivingConfigurationFromAppStoreCatalogReturns422UnprocessableEntityWhenPlatformValidationFailed(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer->setIsSuccessful(false);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/configure', $this->tester->getAppConfigureRequestData());

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->tester->assertAppConfigurationForTenantDoesNotExist($tenantIdentifier);
    }

    public function testReceivingConfigurationFromAppStoreCatalogReturns422UnprocessableEntityWithErrorMessagesWhenPlatformValidationFailed(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $configurationValidationErrorTransfer = new ConfigurationValidationErrorTransfer();
        $configurationValidationErrorTransfer->addErrorMessage('Something went wrong');

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer
            ->setIsSuccessful(false)
            ->addConfigurationValidationError($configurationValidationErrorTransfer);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/configure', $this->tester->getAppConfigureRequestData());

        // Assert
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->tester->assertAppConfigurationForTenantDoesNotExist($tenantIdentifier);
    }

    public function testReceivingConfigurationFromAppStoreCatalogReturns422UnprocessableEntityWithErrorMessagesWhenPlatformValidationThrowsAnException(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => static function (): never {
                throw new Exception('Something went wrong');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/configure', $this->tester->getAppConfigureRequestData());

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->tester->assertAppConfigurationForTenantDoesNotExist($tenantIdentifier);
    }

    public function testDisconnectAppForAnExistingTenantDeactivatesAppConfiguration(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer->setIsSuccessful(false);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/disconnect');

        // Assert
        $this->assertSame(204, $response->getStatusCode());
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }

    public function testDisconnectAppForAnExistingTenantRemovesTenantPaymentsIfPaymentRemoveTenantsPaymentDataFeatureIsEnabled(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);
        $this->tester->setConfig(AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED, true);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/disconnect');

        // Assert
        $this->assertSame(204, $response->getStatusCode());
        $this->tester->dontSeePaymentByTenantIdentifier($tenantIdentifier);
    }

    public function testDisconnectAppForAnExistingTenantDoesntRemoveTenantPaymentsIfPaymentRemoveTenantsPaymentDataFeatureIsDisabled(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);
        $this->tester->setConfig(AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED, false);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/disconnect');

        // Assert
        $this->assertSame(204, $response->getStatusCode());
        $this->tester->seePaymentByTenantIdentifier($tenantIdentifier);
    }

    public function testDisconnectAppForAnExistingTenantWhenNoContentTypeHeaderIsProvidedAppConfigurationIsDeactivated(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->assertAppConfigForTenantEquals($tenantIdentifier);

        $appConfigValidateResponseTransfer = new AppConfigValidateResponseTransfer();
        $appConfigValidateResponseTransfer->setIsSuccessful(false);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'validateConfiguration' => $appConfigValidateResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->setHeaders([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => $tenantIdentifier,
            'Accept-Language' => 'en-US, en;q=0.9,*;q=0.5',
        ]);

        // Act
        $response = $this->tester->sendPost('/private/disconnect');

        // Assert
        $this->assertSame(204, $response->getStatusCode());
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }
}
