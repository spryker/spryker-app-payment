<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Orm\Zed\AppPayment\Persistence\SpyPaymentRefundQuery;
use Spryker\Zed\AppPayment\Persistence\Propel\Payment\Mapper\PaymentMapper;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;

/**
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface getRepository()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface getEntityManager()
 */
class AppPaymentPersistenceFactory extends AbstractPersistenceFactory
{
    public function createPaymentQuery(): SpyPaymentQuery
    {
        return new SpyPaymentQuery();
    }

    public function createPaymentRefundQuery(): SpyPaymentRefundQuery
    {
        return new SpyPaymentRefundQuery();
    }

    public function createPaymentMapper(): PaymentMapper
    {
        return new PaymentMapper();
    }
}
