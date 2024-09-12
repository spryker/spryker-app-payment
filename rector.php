<?php

/**
 * Copyright © 2021-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Ternary\BooleanInTernaryOperatorRuleFixerRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
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
        // Ignore this rule on the AppRouteProviderPlugin as it breaks the code
        CallableThisArrayToAnonymousFunctionRector::class => [
            __DIR__ . '/src/Spryker/Glue/AppPaymentBackendApi/Plugin/GlueApplication/AppPaymentBackendApiRouteProviderPlugin.php',
        ],
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToAppPaymentFacadeBridge.php',
            __DIR__ . '/src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToTranslatorFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppKernelFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToMessageBrokerFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Service/AppPaymentToUtilEncodingServiceBridge.php',
        ],
        AddParamTypeFromPropertyTypeRector::class => [
            __DIR__ . '/src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToAppPaymentFacadeBridge.php',
            __DIR__ . '/src/Spryker/Glue/AppPaymentBackendApi/Dependency/Facade/AppPaymentBackendApiToTranslatorFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToAppKernelFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Facade/AppPaymentToMessageBrokerFacadeBridge.php',
            __DIR__ . '/src/Spryker/Zed/AppPayment/Dependency/Service/AppPaymentToUtilEncodingServiceBridge.php',
        ],
    ]);
};
