<?php
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
