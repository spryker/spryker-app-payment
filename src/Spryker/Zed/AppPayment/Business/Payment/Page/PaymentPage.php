<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Page;

use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPaymentPagePluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException;
use Throwable;

class PaymentPage
{
    use LoggerTrait;

    public function __construct(
        protected PlatformPluginInterface $platformPlugin,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppConfigLoader $appConfigLoader,
        protected AppPaymentConfig $appPaymentConfig
    ) {
    }

    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
    {
        $paymentPageResponseTransfer = new PaymentPageResponseTransfer();
        $paymentPageResponseTransfer
            ->setIsSuccessful(false)
            ->setPaymentPageTemplate('@AppPayment/index/error-page.twig'); // This is the default error page for all errors that can occur before the PaymentPlatformPlugin is executed.

        $requestData = $paymentPageRequestTransfer->getRequestDataOrFail();

        if (!$this->platformPlugin instanceof PlatformPaymentPagePluginInterface) {
            return $this->buildErrorPaymentPageResponse(
                $paymentPageResponseTransfer,
                MessageBuilder::getPlatformPluginDoesNotProvideRenderingAPaymentPage(),
                [
                    PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                    PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
                ],
            );
        }

        if (!$this->validateRequestData($requestData)) {
            return $this->buildErrorPaymentPageResponse(
                $paymentPageResponseTransfer,
                MessageBuilder::getTransactionIdOrTenantIdentifierMissingOrEmpty(),
                [
                    PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                    PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
                ],
            );
        }

        $transactionId = $this->getTransactionId($requestData);
        $tenantIdentifier = $this->getTenantIdentifier($requestData);

        try {
            $paymentTransfer = $this->appPaymentRepository->getPaymentByTransactionId($transactionId);
        } catch (PaymentByTransactionIdNotFoundException $paymentByTransactionIdNotFoundException) {
            return $this->buildErrorPaymentPageResponse(
                $paymentPageResponseTransfer,
                $paymentByTransactionIdNotFoundException->getMessage(),
                [
                    PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                    PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
                ],
            );
        }

        if ($paymentTransfer->getTenantIdentifier() !== $tenantIdentifier) {
            return $this->buildErrorPaymentPageResponse($paymentPageResponseTransfer, MessageBuilder::getInvalidTransactionIdAndTenantIdentifierCombination(), [
                'Requested transactionId' => $transactionId,
                'Requested tenantIdentifier' => $tenantIdentifier,
                'Payment tenantIdentifier' => $paymentTransfer->getTenantIdentifierOrFail(),
            ]);
        }

        $appConfigTransfer = $this->appConfigLoader->loadAppConfig($tenantIdentifier);

        $paymentPageRequestTransfer->setTransactionIdOrFail($transactionId);
        $paymentPageRequestTransfer->setPaymentOrFail($paymentTransfer);
        $paymentPageRequestTransfer->setAppConfigOrFail($appConfigTransfer);
        $paymentPageRequestTransfer->setRedirectUrl($this->getRedirectUrl($paymentTransfer));

        try {
            return $this->platformPlugin->getPaymentPage($paymentPageRequestTransfer);
        } catch (Throwable $throwable) {
            return $this->buildErrorPaymentPageResponse(
                $paymentPageResponseTransfer,
                $throwable->getMessage(),
                [
                    PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                    PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
                ],
            );
        }
    }

    protected function getRedirectUrl(PaymentTransfer $paymentTransfer): string
    {
        return sprintf(
            '%s/app-payment/redirect?%s=%s',
            $this->appPaymentConfig->getZedBaseUrl(),
            PaymentTransfer::TRANSACTION_ID,
            $paymentTransfer->getTransactionId(),
        );
    }

    /**
     * This method will always log the error message and the passed log context.
     *
     * @param array<string, string> $context
     */
    protected function buildErrorPaymentPageResponse(
        PaymentPageResponseTransfer $paymentPageResponseTransfer,
        string $errorMessage,
        array $context = []
    ): PaymentPageResponseTransfer {
        $this->logError($errorMessage, $context);

        $paymentPageData = [
            'errorMessage' => $errorMessage,
        ];
        $paymentPageResponseTransfer->setPaymentPageData($paymentPageData);

        return $paymentPageResponseTransfer;
    }

    // @codingStandardsIgnoreStart
    // For an unknown reason PHPCs is complaining that context could also be a string.
    /**
     * @param array<string, string> $context
     */
    protected function logError(string $errorMessage, array $context): void
    {
        $this->getLogger()->error($errorMessage, $context);
    }

    // @codingStandardsIgnoreEnd

    /**
     * @param array<string, string> $requestData
     */
    protected function getTransactionId(array $requestData): string
    {
        return $requestData['transactionId'];
    }

    /**
     * @param array<string, string> $requestData
     */
    protected function getTenantIdentifier(array $requestData): string
    {
        return $requestData['tenantIdentifier'];
    }

    /**
     * This could be another extension point for letting the `PaymentPlatformPluginInterface` validate the request data.
     *
     * @param array<string, string> $requestData
     */
    protected function validateRequestData(array $requestData): bool
    {
        $isValid = true;

        if (!isset($requestData['transactionId']) || empty($requestData['transactionId'])) {
            $this->logError(MessageBuilder::getRequestTransactionIdIsMissingOrEmpty(), [
                PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
            ]);

            $isValid = false;
        }

        if (!isset($requestData['tenantIdentifier']) || empty($requestData['tenantIdentifier'])) {
            $this->logError(MessageBuilder::getRequestTenantIdentifierIsMissingOrEmpty(), [
                PaymentTransfer::TENANT_IDENTIFIER => $requestData['tenantIdentifier'] ?? '',
                PaymentTransfer::TRANSACTION_ID => $requestData['transactionId'] ?? '',
            ]);

            $isValid = false;
        }

        return $isValid;
    }
}
