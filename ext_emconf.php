<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translatable files',
    'description' => 'Files can present their language variants and use them',
    'category' => 'extension',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99'
        ],
        'conflicts' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'T3G\\AgencyPack\\FileVariants\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'typo3temp/file_variants_uploads',
    'clearCacheOnLoad' => 1,
    'author' => 'Anja Leichsenring',
    'author_email' => 'anja.leichsenring@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '0.8.2',
];
