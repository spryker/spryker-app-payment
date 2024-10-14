<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Controller;

use Generated\Shared\Transfer\RedirectRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface getRepository()
 */
class RedirectController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $transactionId = $request->query->get('transactionId');
        if (empty($transactionId)) {
            return new Response('Transaction ID is missing.', Response::HTTP_BAD_REQUEST);
        }

        $redirectRequestTransfer = (new RedirectRequestTransfer())->setTransactionId((string)$transactionId);
        $redirectResponseTransfer = $this->getFacade()->getRedirectUrl($redirectRequestTransfer);

        return new RedirectResponse($redirectResponseTransfer->getUrlOrFail());
    }
}
