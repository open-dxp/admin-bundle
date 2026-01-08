<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\DataProviderArrayItemsNewLinedRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip([
        DisallowedEmptyRuleFixerRector::class,
        ExplicitBoolCompareRector::class,
        JoinStringConcatRector::class,
        // todo: buggy?
        NullToStrictStringFuncCallArgRector::class,
        // @see https://github.com/rectorphp/rector/issues/9587
        ForeachItemsAssignToEmptyArrayToAssignRector::class,
    ])
    ->withIndent(' ', 4)
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true)
    ->withComposerBased(symfony: true)
    ->withAttributesSets(symfony: true)
    ->withSymfonyContainerPhp(__DIR__ . '/../../var/cache/dev/App_KernelDevDebugContainer.php')
    ->withRules([
        DataProviderArrayItemsNewLinedRector::class,
        ArraySpreadInsteadOfArrayMergeRector::class,
    ])
    ->withTypeCoverageLevel(0);