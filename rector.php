<?php

/**
 * Copyright © 2021-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

use Rector\Config\RectorConfig;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src/Spryker',
        __DIR__ . '/tests/SprykerTest',
    ]);

    $rectorConfig->skip([
        '*/_support/_generated/*',
    ]);

    $rectorConfig->import(SetList::CODE_QUALITY);
    $rectorConfig->import(SetList::CODING_STYLE);
    $rectorConfig->import(SetList::DEAD_CODE);
    $rectorConfig->import(SetList::STRICT_BOOLEANS);
    $rectorConfig->import(SetList::NAMING);
    $rectorConfig->import(SetList::PHP_82);
    $rectorConfig->import(SetList::TYPE_DECLARATION);
    $rectorConfig->import(SetList::EARLY_RETURN);
    $rectorConfig->import(SetList::INSTANCEOF);

    $rectorConfig->ruleWithConfiguration(ClassPropertyAssignToConstructorPromotionRector::class, [
        ClassPropertyAssignToConstructorPromotionRector::INLINE_PUBLIC => true,
    ]);

    $rectorConfig->skip([
        RenameForeachValueVariableToMatchExprVariableRector::class => [
            'src/Spryker/Zed/AppPayment/Business/Payment/Writer/PaymentWriter.php',
        ],
        RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class => [
            'src/Spryker/Glue/AppPaymentBackendApi/Mapper/Payment/GlueResponsePaymentMapper.php',
            'src/Spryker/Zed/AppPayment/Business/MessageBroker/RefundPaymentMessageHandler.php',
            'src/Spryker/Zed/AppPayment/Business/Payment/Transfer/PaymentTransfer.php',
        ],
        // Ignore this rule on the AppRouteProviderPlugin as it breaks the code
        ClassPropertyAssignToConstructorPromotionRector::class => [
            'src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToAppPaymentFacadeBridge.php',
            'src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToTranslatorFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppKernelFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToMessageBrokerFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppWebhookFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Service/AppPaymentToUtilEncodingServiceBridge.php',
        ],
        AddParamTypeFromPropertyTypeRector::class => [
            'src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToAppPaymentFacadeBridge.php',
            'src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToTranslatorFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppKernelFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToMessageBrokerFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppWebhookFacadeBridge.php',
            'src/Spryker/Zed/AppPayment/Dependency/Service/AppPaymentToUtilEncodingServiceBridge.php',
        ],
    ]);
};
