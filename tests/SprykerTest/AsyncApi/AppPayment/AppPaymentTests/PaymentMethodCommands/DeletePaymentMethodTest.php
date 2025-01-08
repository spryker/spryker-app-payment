<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\AppPaymentTests\PaymentMethodCommands;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppKernel\Business\AppKernelFacade;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group AppPaymentTests
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
        $tenantIdentifier = Uuid::uuid4()->toString();

        $appPaymentConfig = new AppPaymentConfig();

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::NAME => 'test-payment-method-name',
            PaymentMethodTransfer::PROVIDER_NAME => 'test-payment-provider-name',
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        // Ensure payment methods are persisted
        $this->tester->seePaymentMethodForTenant('test-payment-method-name', $tenantIdentifier);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->setTenantIdentifier($tenantIdentifier);
        $appConfigTransfer->setIsActive(false);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($deletePaymentMethodTransfer, 'payment-method-commands');
        $this->tester->assertAppConfigurationForTenantIsDeactivated($tenantIdentifier);
    }

    public function testDeletePaymentMethodMessageIsSendWhenAppConfigGetsDeactivatedAndPlatformPluginCanConfigurePaymentMethods(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodNameFoo = Uuid::uuid4()->toString();
        $paymentMethodNameBar = Uuid::uuid4()->toString();

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::NAME => $paymentMethodNameFoo,
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::NAME => $paymentMethodNameBar,
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        // Ensure payment methods are persisted
        $this->tester->seePaymentMethodForTenant($paymentMethodNameFoo, $tenantIdentifier);
        $this->tester->seePaymentMethodForTenant($paymentMethodNameBar, $tenantIdentifier);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPaymentMethodsPluginInterface::class);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_DELETE_PLUGINS, [
            new SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin(),
            new DeleteTenantPaymentsConfigurationAfterDeletePlugin(),
        ]);

        $deletePaymentMethodTransfer = $this->tester->haveDeletePaymentMethodTransfer();

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
