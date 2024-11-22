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
            'typo3' => '11.5.0-12.4.99'
        ],
        'conflicts' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'T3G\\AgencyPack\\FileVariants\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'author' => 'Anja Leichsenring',
    'author_email' => 'anja.leichsenring@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '0.11.2',
];
