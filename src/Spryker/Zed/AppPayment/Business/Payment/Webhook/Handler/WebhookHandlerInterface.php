<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler;

use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;

interface WebhookHandlerInterface
{
    public function handleWebhook(
        WebhookRequestTransfer $webhookRequestTransfer,
        WebhookResponseTransfer $webhookResponseTransfer
    ): WebhookResponseTransfer;
}
