<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Laravel\Set\LaravelSetList;
use Rector\Laravel\Set\LaravelLevelSetList;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        '*/migrations/*',
        '*/cache/*',
        '*/bootstrap/cache/*',
        
        // Skip some rules for Laravel compatibility
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class, // Laravel models can't be readonly
    ])
    ->withSets([
        // PHP Version Sets
        LevelSetList::UP_TO_PHP_82,
        
        // General Sets
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        
        // Laravel Sets
        LaravelSetList::LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelLevelSetList::UP_TO_LARAVEL_110,
    ])
    ->withRules([
        // Type declarations
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromReturnNewRector::class,
        
        // Code quality
        InlineConstructorDefaultToPropertyRector::class,
        SimplifyIfReturnBoolRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        
        // Dead code removal
        RemoveUnusedPrivateMethodRector::class,
        RemoveUnusedPromotedPropertyRector::class,
        RemoveUnusedPrivatePropertyRector::class,
    ])
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: false, // Laravel uses loose comparisons
    )
    ->withParallel(
        maxNumberOfProcess: 16,
        jobSize: 20,
        processTimeout: 180,
    )
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withCache(
        cacheDirectory: __DIR__ . '/.rector-cache',
    )
    ->withTypeCoverageLevel(0); // Start with level 0, increase as code improves
