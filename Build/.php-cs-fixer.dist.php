<?php

$header = <<<EOF
This file is part of the package t3g/file_variants.

For the full copyright and license information, please read the
LICENSE file that was distributed with this source code.
EOF;

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        'header_comment' => [
            'header' => $header
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author',
            ]
        ],
    ])
;
$config
    ->getFinder()
        ->in(dirname(__DIR__))
        ->exclude([
            basename(__DIR__) . '/Web',
        ])
    ;

return $config;
