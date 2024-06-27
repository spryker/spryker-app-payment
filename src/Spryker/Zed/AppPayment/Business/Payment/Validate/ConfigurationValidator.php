<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Validate;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\AppConfigValidateResponseTransfer;
use Generated\Shared\Transfer\GlueErrorTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueRequestValidationTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Service\AppPaymentToUtilEncodingServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ConfigurationValidator
{
    use LoggerTrait;

    public function __construct(
        protected PlatformPluginInterface $platformPlugin,
        protected AppPaymentToUtilEncodingServiceInterface $appPaymentToUtilEncodingService
    ) {
    }

    public function validatePaymentConfiguration(GlueRequestTransfer $glueRequestTransfer): GlueRequestValidationTransfer
    {
        $glueRequestValidationTransfer = (new GlueRequestValidationTransfer())
            ->setIsValid(true)->setStatus(Response::HTTP_OK);

        try {
            $appConfigTransfer = $this->mapGlueRequestTransferToAppConfigTransfer($glueRequestTransfer);
            $appConfigValidateResponseTransfer = $this->platformPlugin->validateConfiguration($appConfigTransfer);
        } catch (Throwable $throwable) {
            return $this->buildFailedResponseFromException($throwable, $glueRequestValidationTransfer);
        }

        if ($appConfigValidateResponseTransfer->getIsSuccessful() === true) {
            return $glueRequestValidationTransfer;
        }

        return $this->mapAppConfigurationValidationResponseTransferToGlueRequestValidationTransfer($appConfigValidateResponseTransfer, $glueRequestValidationTransfer);
    }

    protected function mapAppConfigurationValidationResponseTransferToGlueRequestValidationTransfer(
        AppConfigValidateResponseTransfer $appConfigValidateResponseTransfer,
        GlueRequestValidationTransfer $glueRequestValidationTransfer
    ): GlueRequestValidationTransfer {
        $glueRequestValidationTransfer
            ->setIsValid(false)
            ->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $messages = [];
        foreach ($appConfigValidateResponseTransfer->getConfigurationValidationErrors() as $configurationValidationErrorTransfer) {
            foreach ($configurationValidationErrorTransfer->getErrorMessages() as $errorMessage) {
                $messages[$configurationValidationErrorTransfer->getProperty()][] = $errorMessage;
            }

            $messages[$configurationValidationErrorTransfer->getProperty()] = implode(', ', $messages[$configurationValidationErrorTransfer->getProperty()]);
        }

        $glueRequestValidationTransfer->addError(
            (new GlueErrorTransfer())
                ->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setCode('443')
                ->setMessage(implode(', ', $messages)), // json-encoded messages grouped by properties are not displayed :(
        );

        return $glueRequestValidationTransfer;
    }

    protected function mapGlueRequestTransferToAppConfigTransfer(GlueRequestTransfer $glueRequestTransfer): AppConfigTransfer
    {
        $configuration = $this->getConfiguration($glueRequestTransfer);

        if ($glueRequestTransfer->getLocale() == null) {
            $glueRequestTransfer->setLocale('en_US');
        }

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer->fromArray($configuration, true);
        $appConfigTransfer->setLocale($glueRequestTransfer->getLocaleOrFail());
        $appConfigTransfer->setConfig($configuration);

        return $appConfigTransfer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfiguration(GlueRequestTransfer $glueRequestTransfer): array
    {
        /** @phpstan-var array<array<array<string, mixed>>> */
        $payload = $this->appPaymentToUtilEncodingService->decodeJson((string)$glueRequestTransfer->getContent(), true);

        /** @phpstan-var string */
        $configJson = $payload['data']['attributes']['configuration'] ?? [];

        /** @phpstan-var array<string, mixed> */
        return $this->appPaymentToUtilEncodingService->decodeJson((string)$configJson, true);
    }

    protected function buildFailedResponseFromException(
        Throwable $throwable,
        GlueRequestValidationTransfer $glueRequestValidationTransfer
    ): GlueRequestValidationTransfer {
        $this->getLogger()->error($throwable->getMessage());
        $glueErrorTransfer = new GlueErrorTransfer();
        $glueErrorTransfer
            ->setMessage($throwable->getMessage())
            ->setCode($throwable->getCode());

        $glueRequestValidationTransfer
            ->addError($glueErrorTransfer)
            ->setIsValid(false)
            ->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        return $glueRequestValidationTransfer;
    }
}
