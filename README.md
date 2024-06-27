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


### AppKernel
- \Spryker\Glue\AppPaymentBackendApi\Plugin\AppKernel\PaymentConfigurationValidatorPlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin

### AppWebhook
- \Spryker\Zed\AppPayment\Communication\Plugin\AppWebhook\PaymentWebhookHandlerPlugin

### MessageBroker
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CancelPaymentMessageHandlerPlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CapturePaymentMessageHandlerPlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\RefundPaymentMessageHandlerPlugin

### MessageBrokerAws
- \Spryker\Zed\AppPayment\Communication\Plugin\MessageBrokerAws\ConsumerIdHttpChannelMessageReceiverRequestExpanderPlugin
