<?php

/**
 * This configuration is used for TESTING only and will never be used in production!
 */

use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigUpdatedTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentAuthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Shared\AppKernel\AppKernelConstants;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Shared\GlueBackendApiApplication\GlueBackendApiApplicationConstants;
use Spryker\Shared\GlueJsonApiConvention\GlueJsonApiConventionConstants;
use Spryker\Shared\Http\HttpConstants;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Shared\MessageBroker\MessageBrokerConstants;
use Spryker\Shared\MessageBrokerAws\MessageBrokerAwsConstants;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\MessageBrokerAws\MessageBrokerAwsConfig;

// ----------------------------------------------------------------------------
// ------------------------------ Glue Backend API ----------------------------
// ----------------------------------------------------------------------------
$config[GlueBackendApiApplicationConstants::GLUE_BACKEND_API_HOST] = 'api.payment.local';

$config[KernelConstants::ENABLE_CONTAINER_OVERRIDING] = true;
$config[KernelConstants::PROJECT_NAMESPACES] =
$config[GlueBackendApiApplicationConstants::PROJECT_NAMESPACES] = [
    'Spryker',
];
$config[ZedRequestConstants::ZED_API_SSL_ENABLED] = (bool)getenv('SPRYKER_ZED_SSL_ENABLED');

$config[ApplicationConstants::BASE_URL_ZED] = sprintf(
    'https://%s',
    'api.payment.local',
);

$config[AppKernelConstants::APP_IDENTIFIER] = Uuid::uuid4()->toString();

$config[HttpConstants::URI_SIGNER_SECRET_KEY] = Uuid::uuid4()->toString();

$config[GlueJsonApiConventionConstants::GLUE_DOMAIN] = sprintf(
    '%s://%s',
    getenv('SPRYKER_SSL_ENABLE') ? 'https' : 'http',
    $config[GlueBackendApiApplicationConstants::GLUE_BACKEND_API_HOST] ?: 'localhost',
);

$config[MessageBrokerConstants::MESSAGE_TO_CHANNEL_MAP] = [
    AppConfigUpdatedTransfer::class => 'app-events',
];

$config[MessageBrokerConstants::CHANNEL_TO_SENDER_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_CHANNEL_TRANSPORT,
];

$config[AppPaymentConstants::APP_IDENTIFIER] = Uuid::uuid4()->toString();

$config[MessageBrokerConstants::MESSAGE_TO_CHANNEL_MAP] =
$config[MessageBrokerAwsConstants::MESSAGE_TO_CHANNEL_MAP] = [
    PaymentAuthorizedTransfer::class => 'payment-events',
    PaymentAuthorizationFailedTransfer::class => 'payment-events',
    PaymentCapturedTransfer::class => 'payment-events',
    PaymentCaptureFailedTransfer::class => 'payment-events',
    PaymentRefundedTransfer::class => 'payment-events',
    PaymentRefundFailedTransfer::class => 'payment-events',
    PaymentCanceledTransfer::class => 'payment-events',
    PaymentCancellationFailedTransfer::class => 'payment-events',
    CancelPaymentTransfer::class => 'payment-commands',
    CapturePaymentTransfer::class => 'payment-commands',
    RefundPaymentTransfer::class => 'payment-commands',
    AddPaymentMethodTransfer::class => 'payment-method-commands',
    DeletePaymentMethodTransfer::class => 'payment-method-commands',
    PaymentCreatedTransfer::class => 'payment-events',
    // App event
    AppConfigUpdatedTransfer::class => 'app-events',
];

$config[MessageBrokerConstants::CHANNEL_TO_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-method-commands' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-commands' => MessageBrokerAwsConfig::SQS_TRANSPORT,
];

$config[MessageBrokerAwsConstants::CHANNEL_TO_SENDER_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-method-commands' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
];

$config[MessageBrokerAwsConstants::CHANNEL_TO_RECEIVER_TRANSPORT_MAP] = [
    'payment-commands' => MessageBrokerAwsConfig::SQS_TRANSPORT,
];
