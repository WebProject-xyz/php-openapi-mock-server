<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // Target PHP version
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        naming: true,
    )
    ->withSets([
        SetList::CODING_STYLE,
        SetList::INSTANCEOF,
    ])
    // Import settings
    ->withImportNames(
        importNames: true,
        removeUnusedImports: true,
    )
    // PHPStan config for better type inference
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon',
        __DIR__ . '/vendor/slam/phpstan-laminas-framework/extension.neon',
    ])
    // Parallel processing
    ->withParallel(
        timeoutSeconds: 120,
        maxNumberOfProcess: 8,
    )
    ->withSkip([
        __DIR__ . '/tests/Support/_generated',
    ]);
