<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppConfig;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

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
}
