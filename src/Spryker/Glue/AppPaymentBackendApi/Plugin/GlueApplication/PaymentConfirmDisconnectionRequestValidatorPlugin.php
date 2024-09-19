<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication;

use Generated\Shared\Transfer\GlueErrorTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Generated\Shared\Transfer\PaymentConditionsTransfer;
use Generated\Shared\Transfer\PaymentCriteriaTransfer;
use Spryker\Glue\AppKernel\Plugin\GlueApplication\AbstractConfirmDisconnectionRequestValidatorPlugin;
use Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiConfig;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class PaymentConfirmDisconnectionRequestValidatorPlugin extends AbstractConfirmDisconnectionRequestValidatorPlugin
{
    protected function validateDisconnectionRequest(GlueRequestTransfer $glueRequestTransfer, string $tenantIdentifier): GlueRequestValidationTransfer
    {
        $paymentCollectionTransfer = $this->getFactory()->getAppPaymentFacade()->getPaymentCollection(
            (new PaymentCriteriaTransfer())->setPaymentConditions(
                (new PaymentConditionsTransfer())
                    ->setTenantIdentifier($tenantIdentifier)
                    ->setExcludingStatuses([
                        PaymentStatus::STATUS_CANCELED,
                        PaymentStatus::STATUS_SUCCEEDED,
                    ]),
            ),
        );

        if ($paymentCollectionTransfer->getPayments()->count() === 0) {
            return (new GlueRequestValidationTransfer())
                ->setIsValid(true);
        }

        return (new GlueRequestValidationTransfer())
            ->setIsValid(false)
            ->addError(
                (new GlueErrorTransfer())
                    ->setCode(AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_CANNOT_BE_PROCEEDED)
                    ->setMessage(
                        $this->getFactory()->getTranslatorFacade()->trans('The payment App cannot be disconnected when there are open orders. Open orders won’t be proceed automatically if you delete the App. Close the open orders to continue.'),
                    ),
            );
    }

    protected function getCancellationErrorCode(): string
    {
        return AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_FORBIDDEN;
    }

    protected function getCancellationErrorMessage(): string
    {
        return $this->getFactory()->getTranslatorFacade()->trans('Please close any open orders and try again.');
    }
}
