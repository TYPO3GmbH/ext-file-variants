<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

defined('TYPO3_MODE') or die();

call_user_func(function () {

//    $persistenceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\T3G\AgencyPack\FileVariants\Service\PersistenceService::class);
//    /** @var \T3G\AgencyPack\FileVariants\Service\RecordService $recordService */
//    $recordService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\T3G\AgencyPack\FileVariants\Service\RecordService::class, $persistenceService);

    foreach ($GLOBALS['TCA'] as $table => $config) {
        //   if ($recordService->isFalConsumingTable($table)) {
        // streamline language sync for all FAL fields
        foreach ($config['columns'] as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['config']['foreign_table']) && $fieldConfig['config']['foreign_table'] === 'sys_file_reference') {
                if (isset($fieldConfig['config']['behaviour']['localizationMode'])) {
                    unset($GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['behaviour']['localizationMode']);
                }
            }
        }
        //     }
        // deactivate sys_language_uid = -1
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            if (isset($config['columns'][$languageField])) {
                $fieldConfig = $config['columns'][$languageField]['config'];
                if (isset($fieldConfig['items'])) {
                    foreach ($fieldConfig['items'] as $index => $item) {
                        if ((int)$item[1] === -1) {
                            unset($GLOBALS['TCA'][$table]['columns'][$languageField]['config']['items'][$index]);
                        }
                    }
                }
            }
        }
    }
});
