<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentMethodCommands;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppKernel\Business\AppKernelFacade;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentMethodCommands
 * @group DeletePaymentMethodTest
 * Add your own group annotations below this line
 */
class DeletePaymentMethodTest extends Unit
{
    protected AppPaymentAsyncApiTester $tester;

    public function testDeletePaymentMethodMessageIsSendWhenAppConfigGetsDeactivated(): void
    {
        // Arrange
        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);

        $tenantIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->setTenantIdentifier($tenantIdentifier);
        $appConfigTransfer->setIsActive(false);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($deletePaymentMethodTransfer, 'payment-method-commands');
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }

    public function testDeletePaymentMethodMessageIsSendAndPaymentAreDeletedWhenAppConfigGetsDeactivated(): void
    {
        // Arrange
        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);
        $this->tester->setConfig(AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED, true);

        $tenantIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->setTenantIdentifier($tenantIdentifier);
        $appConfigTransfer->setIsActive(false);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($deletePaymentMethodTransfer, 'payment-method-commands');
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }

    /**
     * @deprecated Can be removed with teh next major. The plugin used in this test will be removed.
     */
    public function testDeletePaymentMethodMessageIsSendWhenAppConfigGetsDeactivatedDeprecated(): void
    {
        // Arrange
        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);

        $tenantIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->setTenantIdentifier($tenantIdentifier);
        $appConfigTransfer->setIsActive(false);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($deletePaymentMethodTransfer, 'payment-method-commands');
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }

    /**
     * @deprecated Can be removed with teh next major. The plugin used in this test will be removed.
     */
    public function testDeletePaymentMethodMessageIsSendAndPaymentAreDeletedWhenAppConfigGetsDeactivatedDeprecated(): void
    {
        // Arrange
        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);
        $this->tester->setConfig(AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED, true);

        $tenantIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->setTenantIdentifier($tenantIdentifier);
        $appConfigTransfer->setIsActive(false);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($deletePaymentMethodTransfer, 'payment-method-commands');
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }
}
