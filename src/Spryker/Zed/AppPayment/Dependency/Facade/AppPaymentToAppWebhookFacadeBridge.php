<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Facade;

use Generated\Shared\Transfer\WebhookInboxCriteriaTransfer;

class AppPaymentToAppWebhookFacadeBridge implements AppPaymentToAppWebhookFacadeInterface
{
    /**
     * @var \Spryker\Zed\AppWebhook\Business\AppWebhookFacadeInterface
     */
    protected $appWebhookFacade;

    /**
     * @param \Spryker\Zed\AppWebhook\Business\AppWebhookFacadeInterface $appWebhookFacade
     */
    public function __construct($appWebhookFacade)
    {
        $this->appWebhookFacade = $appWebhookFacade;
    }

    public function processUnprocessedWebhooks(WebhookInboxCriteriaTransfer $webhookInboxCriteriaTransfer): void
    {
        $this->appWebhookFacade->processUnprocessedWebhooks($webhookInboxCriteriaTransfer);
    }

    public function deleteWebhooks(WebhookInboxCriteriaTransfer $webhookInboxCriteriaTransfer): void
    {
        $this->appWebhookFacade->deleteWebhooks($webhookInboxCriteriaTransfer);
    }
}
