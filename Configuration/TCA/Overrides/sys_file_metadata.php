<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3_MODE') or die();

call_user_func(function () {
    $GLOBALS['TCA']['sys_file_metadata']['ctrl']['container'] = [
    'outerWrapContainer' => [
        'fieldWizard' => [
            'FileVariantsOverviewWizard' => [
                'renderType' => 'FileVariantsOverviewWizard',
            ],
        ],
    ],
];
});
