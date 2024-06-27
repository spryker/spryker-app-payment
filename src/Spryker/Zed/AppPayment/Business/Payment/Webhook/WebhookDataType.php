<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook;

enum WebhookDataType
{
    /**
     * @var string
     */
    public const PAYMENT = 'payment';

    /**
     * @var string
     */
    public const REFUND = 'refund';

    /**
     * @var string
     */
    public const ACCOUNT = 'account';
}
