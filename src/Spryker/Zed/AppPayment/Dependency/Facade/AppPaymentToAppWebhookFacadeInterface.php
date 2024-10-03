<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Facade;

use Generated\Shared\Transfer\WebhookInboxCriteriaTransfer;

interface AppPaymentToAppWebhookFacadeInterface
{
    public function processUnprocessedWebhooks(WebhookInboxCriteriaTransfer $webhookInboxCriteriaTransfer): void;

    public function deleteWebhooks(WebhookInboxCriteriaTransfer $webhookInboxCriteriaTransfer): void;
}
