<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentMethodCommands;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppKernel\Business\AppKernelFacade;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentMethodCommands
 * @group AddPaymentMethodTest
 * Add your own group annotations below this line
 */
class AddPaymentMethodTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    protected function _before(): void
    {
        // Disable all plugins that might be registered in the core.
        $this->getDependencyHelper()->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->getDependencyHelper()->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new SendAddPaymentMethodMessageConfigurationAfterSavePlugin()]);
    }

    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsNew(): void
    {
        // Arrange
        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsConnected(): void
    {
        // Arrange
        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true)
            ->setStatus(AppKernelConfig::APP_STATUS_CONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    public function testAddPaymentMethodMessageIsNotSendWhenAppConfigStateIsDisconnected(): void
    {
        // Arrange
        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setStatus(AppKernelConfig::APP_STATUS_DISCONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasNotSent(AddPaymentMethodTransfer::class);
    }
}
