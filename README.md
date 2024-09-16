# AppPayment Module
[![Latest Stable Version](https://poser.pugx.org/spryker/app-payment/v/stable.svg)](https://packagist.org/packages/spryker/app-payment)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)

Provides SyncAPI and AsyncAPI schema files and the needed code to be used in a Payment Service Provider App.

## Installation

```
composer require spryker/app-payment
```

### Configure

#### App Identifier

config/Shared/config_default.php

```
use Spryker\Shared\AppPayment\AppConstants;

$config[AppConstants::APP_IDENTIFIER] = getenv('APP_IDENTIFIER') ?: 'hello-world';
```

### Testing the AppPayment

You can test the AppPayment as usual with Codeception. Before that you need to run some commands:

```
composer setup
```

With these commands you've set up the AppPayment and can start the tests

```
vendor/bin/codecept build
vendor/bin/codecept run
```

## Plugins

The following plugins can be used inside your Payment Service Provider App.

### GlueApplication

#### \Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication\AppPaymentBackendApiRouteProviderPlugin

This plugin provides the routes for the AppPaymentBackendApi module.


###### Routes provided

- /private/initialize-payment - Used from the Tenant side to initialize a payment.
- /private/confirm-pre-order-payment - Used from the Tenant side to confirm pre-order payment after the order was persisted.

### AppKernel
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\ConfigurePaymentMethodsConfigurationAfterSavePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin

### AppWebhook
- \Spryker\Zed\AppPayment\Communication\Plugin\AppWebhook\PaymentWebhookHandlerPlugin

### MessageBroker
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CancelPaymentMessageHandlerPlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CapturePaymentMessageHandlerPlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\RefundPaymentMessageHandlerPlugin

### MessageBrokerAws
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBrokerAws\ConsumerIdHttpChannelMessageReceiverRequestExpanderPlugin

## Extensions

- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformMarketplacePluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentPagePluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPreOrderPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface

### AppPaymentPlatformMarketplacePluginInterface

You can implement this plugin when your PSP App supports Marketplace capabilities.

### AppPaymentPlatformPaymentMethodsPluginInterface

This plugin must be implemented to provide the payment methods that the PSP App supports.

### AppPaymentPlatformPaymentPagePluginInterface

This plugin can be implemented to provide a payment page that the PSP App supports. This is only needed when using the redirect flows after the order was created.

### AppPaymentPlatformPluginInterface

This is the root plugin which must be implemented to provide the PSP App capabilities from the project level.

### AppPaymentPlatformPreOrderPluginInterface

This plugin can be implemented to provide the pre-order capabilities that the PSP App supports.

When using the pre-order payment flow, the InitializePayment API endpoint is used before the order gets persisted and it returns the needed data for an headless approach to add the payment page on project side. Usually, this is done via a provided JavaScript that is send to the frontend in the InitializePayment API call response.

On project side the customer than makes the Payment via the provided JavaScripts and the payment page provided by the PSP provider. After the order is persistzed on the project side a call to the ConfirmPreOrderPayment API endpoint is made to confirm the payment and connect it with the orderReference.

### PaymentTransmissionsRequestExtenderPluginInterface

This plugin can be implemented to extend the request data that is send to the PSP App when doing payouts to Merchants. This is usually only needed when the PSP App supports Marketplace capabilities.

## Configure the MessageBroker

Add this to your project configuration:

```
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
    UpdatePaymentMethodTransfer::class => 'payment-method-commands',
    DeletePaymentMethodTransfer::class => 'payment-method-commands',
    PaymentCreatedTransfer::class => 'payment-events',
    PaymentUpdatedTransfer::class => 'payment-events',
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
```
