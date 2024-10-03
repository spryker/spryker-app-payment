<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\GlueErrorConfirmTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Glue\AppKernel\AppKernelConfig;
use Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiConfig;
use Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiDependencyProvider;
use Spryker\Glue\AppPaymentBackendApi\Dependency\Facade\AppPaymentBackendApiToTranslatorFacadeInterface;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication\PaymentConfirmDisconnectionRequestValidatorPlugin;
use SprykerTest\Glue\AppPaymentBackendApi\AppPaymentBackendApiPluginTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group AppPaymentBackendApi
 * @group Plugin
 * @group PaymentConfirmDisconnectionRequestValidatorPluginTest
 * Add your own group annotations below this line
 */
class PaymentConfirmDisconnectionRequestValidatorPluginTest extends Unit
{
    /**
     * @var string
     */
    protected const TENANT_IDENTIFIER = 'tenant-identifier';

    /**
     * @see \Spryker\Glue\AppKernel\Plugin\GlueApplication\AbstractConfirmDisconnectionRequestValidatorPlugin::HEADER_CONFIRMATION_STATUS
     *
     * @var string
     */
    protected const HEADER_CONFIRMATION_STATUS = 'x-confirmation-status';

    protected AppPaymentBackendApiPluginTester $tester;

    public function testPaymentConfirmDisconnectionRequestValidatorPluginReturnsErrorIfThereIsNoExistingTenant(): void
    {
        // Arrange
        $this->tester->setDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR, $this->getTranslatorFacadeMock());

        $paymentConfirmDisconnectionRequestValidatorPlugin = new PaymentConfirmDisconnectionRequestValidatorPlugin();

        // Act
        $glueRequestValidationTransfer = $paymentConfirmDisconnectionRequestValidatorPlugin
            ->validate(new GlueRequestTransfer());

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertCount(1, $glueRequestValidationTransfer->getErrors());
        $this->assertSame(
            AppKernelConfig::ERROR_CODE_PAYMENT_DISCONNECTION_TENANT_IDENTIFIER_MISSING,
            $glueRequestValidationTransfer->getErrors()[0]->getCode(),
        );
    }

    public function testPaymentConfirmDisconnectionRequestValidatorPluginReturnsSuccessIfThereAreNoPaymentsForExistingTenant(): void
    {
        // Arrange
        $this->tester->setDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR, $this->getTranslatorFacadeMock());

        $paymentConfirmDisconnectionRequestValidatorPlugin = new PaymentConfirmDisconnectionRequestValidatorPlugin();
        $glueRequestTransfer = new GlueRequestTransfer();
        $glueRequestTransfer->setMeta([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => [static::TENANT_IDENTIFIER],
        ]);

        // Act
        $glueRequestValidationTransfer = $paymentConfirmDisconnectionRequestValidatorPlugin
            ->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
    }

    public function testPaymentConfirmDisconnectionRequestValidatorPluginReturnsErrorIfThereArePaymentsForExistingTenant(): void
    {
        // Arrange
        $this->tester->setDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR, $this->getTranslatorFacadeMock());

        $paymentConfirmDisconnectionRequestValidatorPlugin = new PaymentConfirmDisconnectionRequestValidatorPlugin();
        $glueRequestTransfer = new GlueRequestTransfer();
        $glueRequestTransfer->setMeta([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => [static::TENANT_IDENTIFIER],
        ]);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => static::TENANT_IDENTIFIER,
        ]);

        // Act
        $glueRequestValidationTransfer = $paymentConfirmDisconnectionRequestValidatorPlugin
            ->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertCount(1, $glueRequestValidationTransfer->getErrors());
        $this->assertSame(
            AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_CANNOT_BE_PROCEEDED,
            $glueRequestValidationTransfer->getErrors()[0]->getCode(),
        );
        $this->assertInstanceOf(GlueErrorConfirmTransfer::class, $glueRequestValidationTransfer->getErrors()[0]->getConfirm());
    }

    /**
     * @dataProvider confirmationStatusDataProvider
     *
     * @param string $confirmationStatus
     *
     * @return void
     */
    public function testPaymentConfirmDisconnectionRequestValidatorPluginReturnsErrorIfThereArePaymentsForExistingTenantAndTheRequestContainsConfirmationCanceledResponse(
        string $confirmationStatus
    ): void {
        // Arrange
        $this->tester->setDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR, $this->getTranslatorFacadeMock());

        $paymentConfirmDisconnectionRequestValidatorPlugin = new PaymentConfirmDisconnectionRequestValidatorPlugin();
        $glueRequestTransfer = new GlueRequestTransfer();
        $glueRequestTransfer->setMeta([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => [static::TENANT_IDENTIFIER],
            static::HEADER_CONFIRMATION_STATUS => [$confirmationStatus],
        ]);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => static::TENANT_IDENTIFIER,
        ]);

        // Act
        $glueRequestValidationTransfer = $paymentConfirmDisconnectionRequestValidatorPlugin
            ->validate($glueRequestTransfer);

        // Assert
        $this->assertFalse($glueRequestValidationTransfer->getIsValid());
        $this->assertCount(1, $glueRequestValidationTransfer->getErrors());
        $this->assertSame(
            AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_FORBIDDEN,
            $glueRequestValidationTransfer->getErrors()[0]->getCode(),
        );
    }

    public function testPaymentConfirmDisconnectionRequestValidatorPluginReturnsSuccessIfThereArePaymentsForExistingTenantAndTheRequestContainsConfirmationSuccessfulResponse(): void
    {
        // Arrange
        $this->tester->setDependency(AppPaymentBackendApiDependencyProvider::FACADE_TRANSLATOR, $this->getTranslatorFacadeMock());

        $paymentConfirmDisconnectionRequestValidatorPlugin = new PaymentConfirmDisconnectionRequestValidatorPlugin();
        $glueRequestTransfer = new GlueRequestTransfer();
        $glueRequestTransfer->setMeta([
            GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER => [static::TENANT_IDENTIFIER],
            static::HEADER_CONFIRMATION_STATUS => ['true'],
        ]);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => static::TENANT_IDENTIFIER,
        ]);

        // Act
        $glueRequestValidationTransfer = $paymentConfirmDisconnectionRequestValidatorPlugin
            ->validate($glueRequestTransfer);

        // Assert
        $this->assertTrue($glueRequestValidationTransfer->getIsValid());
    }

    protected function getTranslatorFacadeMock(): AppPaymentBackendApiToTranslatorFacadeInterface
    {
        return $this->getMockBuilder(AppPaymentBackendApiToTranslatorFacadeInterface::class)->getMock();
    }

    /**
     * @return array<string, array<string>>
     */
    public function confirmationStatusDataProvider(): array
    {
        return [
            'confirmation status false' => ['false'],
            'confirmation status double false' => ['false,true'],
            'confirmation status any value' => ['value'],
        ];
    }
}
