<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence\Exception;

use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;

class RefundByRefundIdNotFoundException extends PaymentException
{
    public function __construct(string $refundId)
    {
        parent::__construct(MessageBuilder::refundByRefundIdNotFound($refundId));
    }
}
