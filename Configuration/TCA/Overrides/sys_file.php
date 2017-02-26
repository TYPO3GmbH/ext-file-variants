<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    $tempColumns = [
        'sys_language_uid' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file',
                'foreign_table_where' => 'AND sys_file.uid=###REC_FIELD_l10n_parent### AND sys_file.sys_language_uid IN (-1,0)',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file', $tempColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_file', 'sys_language_uid, l10n_parent');

    $GLOBALS['TCA']['sys_file']['ctrl']['languageField'] = 'sys_language_uid';
    $GLOBALS['TCA']['sys_file']['ctrl']['transOrigPointerField'] = 'l10n_parent';
    $GLOBALS['TCA']['sys_file']['ctrl']['transOrigDiffSourceField'] = 'l10n_diffsource';
    $GLOBALS['TCA']['sys_file']['ctrl']['hideTable'] = false;
    $GLOBALS['TCA']['sys_file']['ctrl']['rootLevel'] = 0;
});
