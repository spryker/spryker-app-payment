<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Service;

interface AppPaymentToUtilEncodingServiceInterface
{
    /**
     * @return object|array<mixed>|null
     */
    public function decodeJson($jsonValue, $assoc = false, $depth = null, $options = null);
}
