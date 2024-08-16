<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Generated\Shared\Transfer\RedirectRequestTransfer;
use Generated\Shared\Transfer\RedirectResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory getFactory()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface getRepository()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface getEntityManager()
 */
class AppPaymentFacade extends AbstractFacade implements AppPaymentFacadeInterface
{
    /**
     * @api
     *
     * @inheritDoc
     */
    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        return $this->getFactory()->createPayment()->initializePayment($initializePaymentRequestTransfer);
    }

    /**
     * @api
     *
     * @inheritDoc
     */
    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
    {
        return $this->getFactory()->createPayment()->getPaymentPage($paymentPageRequestTransfer);
    }

    /**
     * @api
     *
     * @inheritDoc
     */
    public function handleWebhook(WebhookRequestTransfer $webhookRequestTransfer, WebhookResponseTransfer $webhookResponseTransfer): WebhookResponseTransfer
    {
        return $this->getFactory()->createWebhookHandler()->handleWebhook($webhookRequestTransfer, $webhookResponseTransfer);
    }

    /**
     * @api
     *
     * @inheritDoc
     */
    public function sendAddPaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        return $this->getFactory()->createMessageSender()->sendAddPaymentMethodMessage($appConfigTransfer);
    }

    /**
     * @api
     *
     * @inheritDoc
     */
    public function sendDeletePaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        return $this->getFactory()->createMessageSender()->sendDeletePaymentMethodMessage($appConfigTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function handleCancelPayment(CancelPaymentTransfer $cancelPaymentTransfer): void
    {
        $this->getFactory()->createCancelPaymentMessageHandler()->handleCancelPayment($cancelPaymentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function handleCapturePayment(CapturePaymentTransfer $capturePaymentTransfer): void
    {
        $this->getFactory()->createCapturePaymentMessageHandler()->handleCapturePayment($capturePaymentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function handleRefundPayment(RefundPaymentTransfer $refundPaymentTransfer): void
    {
        $this->getFactory()->createRefundPaymentMessageHandler()->handleRefundPayment($refundPaymentTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRedirectUrl(RedirectRequestTransfer $redirectRequestTransfer): RedirectResponseTransfer
    {
        return $this->getFactory()->createRedirect()->getRedirectUrl($redirectRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deletePaymentCollection(
        PaymentCollectionDeleteCriteriaTransfer $paymentCollectionDeleteCriteriaTransfer
    ): void {
        $this->getEntityManager()->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer
    {
        return $this->getFactory()->createPayment()->transferPayments($paymentTransmissionsRequestTransfer);
    }
}
