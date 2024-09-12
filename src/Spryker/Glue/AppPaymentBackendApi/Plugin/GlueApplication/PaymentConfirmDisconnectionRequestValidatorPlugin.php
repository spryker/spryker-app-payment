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
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class PaymentConfirmDisconnectionRequestValidatorPlugin extends AbstractConfirmDisconnectionRequestValidatorPlugin
{
    protected function getLabelOk(): string
    {
        return $this->getFactory()->getTranslatorFacade()->trans('Ignore & Disconnect');
    }

    protected function getLabelCancel(): string
    {
        return $this->getFactory()->getTranslatorFacade()->trans('Cancel');
    }

    protected function validateDisconnectionRequest(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        if (empty($glueRequestTransfer->getMeta()[GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER])) {
            return (new GlueRequestValidationTransfer())
                ->setIsValid(false)
                ->addError(
                    (new GlueErrorTransfer())
                        ->setCode(AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_TENANT_IDENTIFIER_MISSING)
                        ->setMessage(
                            $this->getFactory()->getTranslatorFacade()->trans('Tenant identifier is missing.'),
                        ),
                );
        }

        $paymentCollectionTransfer = $this->getFactory()->getAppPaymentFacade()->getPaymentCollection(
            (new PaymentCriteriaTransfer())->setPaymentConditions(
                (new PaymentConditionsTransfer())
                    ->setTenantIdentifier($glueRequestTransfer->getMeta()[GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER][0])
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
                    ->setMessage($this->getConfirmationErrorMessage()),
            );
    }

    protected function onConfirmationOk(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        return (new GlueRequestValidationTransfer())
            ->setIsValid(true);
    }

    protected function onConfirmationCancel(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        return (new GlueRequestValidationTransfer())
            ->setIsValid(false)
            ->setStatus(Response::HTTP_BAD_REQUEST)
            ->addError(
                (new GlueErrorTransfer())
                    ->setCode(AppPaymentBackendApiConfig::ERROR_CODE_PAYMENT_DISCONNECTION_FORBIDDEN)
                    ->setStatus(Response::HTTP_BAD_REQUEST)
                    ->setMessage($this->getCancellationErrorMessage()),
            );
    }

    protected function getConfirmationErrorMessage(): string
    {
        return $this->getFactory()->getTranslatorFacade()->trans('The payment App cannot be disconnected when there are open orders. Open orders won’t be proceed automatically if you delete the App. Close the open orders to continue.');
    }

    protected function getCancellationErrorMessage(): string
    {
        return $this->getFactory()->getTranslatorFacade()->trans('Please close any open orders and try again.');
    }
}
