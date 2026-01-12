<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO312\v3\MigrateItemsIndexedKeysToAssociativeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/*.php',
        __DIR__ . '/../Classes',
        __DIR__ . '/../Configuration',
        __DIR__ . '/../Tests',
    ])
    ->withRootFiles()
    ->withPhpSets()
    // minimum version for TYPO3 v12 is 8.1
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSets([
        Typo3SetList::TYPO3_12,
    ])
    ->withSkip([
        MigrateItemsIndexedKeysToAssociativeRector::class,
    ])
;
