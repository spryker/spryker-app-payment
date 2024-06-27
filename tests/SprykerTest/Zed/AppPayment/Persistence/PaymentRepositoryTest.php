<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Persistence;

use Codeception\Test\Unit;
use Exception;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepository;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Persistence
 * @group PaymentRepositoryTest
 * Add your own group annotations below this line
 */
class PaymentRepositoryTest extends Unit
{
    public function testGetByTransactionIdThrowsAnExceptionWhenNoPaymentForTransactionIdFound(): void
    {
        // Arrange
        $paymentRepository = new AppPaymentRepository();
        $transactionId = Uuid::uuid4()->toString();

        // Expect
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(MessageBuilder::paymentByTransactionIdNotFound($transactionId));

        // Act
        $paymentRepository->getPaymentByTransactionId($transactionId);
    }
}
