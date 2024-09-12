<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Dependency\Facade;

use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCollectionTransfer;
use Generated\Shared\Transfer\PaymentCriteriaTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;

class AppPaymentBackendApiToAppPaymentFacadeBridge implements AppPaymentBackendApiToAppPaymentFacadeInterface
{
    /**
     * @var \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface
     */
    protected $appPaymentFacade;

    /**
     * @param \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface $appPaymentFacade
     */
    public function __construct($appPaymentFacade)
    {
        $this->appPaymentFacade = $appPaymentFacade;
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        return $this->appPaymentFacade->initializePayment($initializePaymentRequestTransfer);
    }

    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer
    {
        return $this->appPaymentFacade->transferPayments($paymentTransmissionsRequestTransfer);
    }

    public function getPaymentCollection(PaymentCriteriaTransfer $paymentCriteriaTransfer): PaymentCollectionTransfer
    {
        return $this->appPaymentFacade->getPaymentCollection($paymentCriteriaTransfer);
    }
}
