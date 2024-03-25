<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Build/*.php',
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Tests',
    ])
    ->withRootFiles()
    ->withPhpSets()
;
