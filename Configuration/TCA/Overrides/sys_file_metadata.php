<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $tempColumns = [
        'language_variant' => [
            'label' => 'LLL:EXT:file_variants/Resources/Private/Language/locallang.xlf:sys_file_metadata.language_variant',
            'exclude' => 1,
            'displayCond' => 'FIELD:sys_language_uid:!=:0',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                ],
                'uploadfolder' => 'uploads/tx_filevariants',
                'size' => 1,
                'maxsize' => 50000,
                'maxitems' => 1,
                'allowed' => 'jpg, jpeg, png, gif',
            ]
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $tempColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_file_metadata', 'language_variant', '', 'after:fileinfo');
});
