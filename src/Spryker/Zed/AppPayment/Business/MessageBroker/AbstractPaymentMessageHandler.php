<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\AppConfigCriteriaTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;

class AbstractPaymentMessageHandler
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected TenantIdentifierExtractor $tenantIdentifierExtractor,
        protected AppPaymentToAppKernelFacadeInterface $appPaymentToAppKernelFacade
    ) {
    }

    protected function getPayment(CancelPaymentTransfer|CapturePaymentTransfer|RefundPaymentTransfer $messageTransfer): ?PaymentTransfer
    {
        $tenantIdentifier = $this->tenantIdentifierExtractor->getTenantIdentifierFromMessage($messageTransfer);

        $appConfigCriteriaTransfer = new AppConfigCriteriaTransfer();
        $appConfigCriteriaTransfer->setTenantIdentifier($tenantIdentifier);

        $appConfigTransfer = $this->appPaymentToAppKernelFacade->getConfig($appConfigCriteriaTransfer);

        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED) {
            return null;
        }

        try {
            return $this->appPaymentRepository->getPaymentByTenantIdentifierAndOrderReference(
                $tenantIdentifier,
                $messageTransfer->getOrderReferenceOrFail(),
            );
        } catch (PaymentByTenantIdentifierAndOrderReferenceNotFoundException $paymentByTenantIdentifierAndOrderReferenceNotFoundException) {
            $this->getLogger()->warning($paymentByTenantIdentifierAndOrderReferenceNotFoundException->getMessage());

            return null;
        }
    }
}
