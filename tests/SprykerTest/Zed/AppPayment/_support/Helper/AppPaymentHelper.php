<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Helper;

use Codeception\Module;
use Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Zed\Testify\Helper\Business\BusinessHelperTrait;
use Symfony\Component\HttpFoundation\Request;

class AppPaymentHelper extends Module
{
    use BusinessHelperTrait;
    use DataCleanupHelperTrait;

    public function getPaymentBusinessFactory(): AppPaymentBusinessFactory
    {
        /** @var \Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory */
        return $this->getBusinessHelper()->getFactory();
    }

    /**
     * Used by Payment Communication tests.
     *
     * @param string|null $transactionId
     * @param string|null $tenantIdentifier
     */
    public function getPaymentPageRequest(?string $transactionId = null, ?string $tenantIdentifier = null): Request
    {
        $requestData = [];

        if ($transactionId !== null && $transactionId !== '') {
            $requestData['transactionId'] = $transactionId;
        }

        if ($tenantIdentifier !== null && $tenantIdentifier !== '') {
            $requestData['tenantIdentifier'] = $tenantIdentifier;
        }

        return Request::create('/payment', Request::METHOD_GET, $requestData);
    }
}
