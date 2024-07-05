<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Shared\Kernel\Transfer\Exception\NullValueException;
use SprykerTest\Zed\AppPayment\AppPaymentBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Business
 * @group Facade
 * @group PaymentFacadeTest
 * Add your own group annotations below this line
 */
class PaymentFacadeTest extends Unit
{
    protected AppPaymentBusinessTester $tester;

    /**
     * @return void
     */
    public function testDeletePaymentCollectionRemovesAllTheTenantPaymentsWithTenantIdentifierSpecifiedInPaymentCollectionDeleteCriteria(): void
    {
        // Arrange
        $tenantIdentifierToDelete = Uuid::uuid4()->toString();
        $tenantIdentifierToCheck = Uuid::uuid4()->toString();

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifierToDelete,
        ]);
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifierToCheck,
        ]);

        $paymentCollectionDeleteCriteriaTransfer = (new PaymentCollectionDeleteCriteriaTransfer())
            ->setTenantIdentifier($tenantIdentifierToDelete);

        // Act
        $this->tester->getFacade()
            ->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);

        // Assert
        $this->tester->dontSeePaymentByTenantIdentifier($tenantIdentifierToDelete);
        $this->tester->seePaymentByTenantIdentifier($tenantIdentifierToCheck);
    }

    /**
     * @return void
     */
    public function testDeletePaymentCollectionThrowsAnExceptionWhenPaymentCollectionDeleteCriteriaDoesNotContainAtLeastOneTenantIdentifier(): void
    {
        // Arrange
        $paymentCollectionDeleteCriteriaTransfer = (new PaymentCollectionDeleteCriteriaTransfer());

        // Assert
        $this->expectException(NullValueException::class);

        // Act
        $this->tester->getFacade()
            ->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);
    }
}
