<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Controller;

use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\Communication\AppPaymentCommunicationFactory getFactory()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface getRepository()
 */
class IndexController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $paymentPageRequestTransfer = (new PaymentPageRequestTransfer())->setRequestData($request->query->all());
        $paymentPageResponseTransfer = $this->getFacade()->getPaymentPage($paymentPageRequestTransfer);

        return $this->renderView(
            $paymentPageResponseTransfer->getPaymentPageTemplateOrFail(),
            $paymentPageResponseTransfer->getPaymentPageData(), // Not all pages may require payment page data.
        );
    }
}
