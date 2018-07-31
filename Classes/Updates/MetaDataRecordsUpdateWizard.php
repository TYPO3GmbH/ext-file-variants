<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Updates;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use T3G\AgencyPack\FileVariants\Service\ResourcesService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Looks after translated filemetadata records and copies the related files into
 * translation storage, if not done yet.
 * Makes sure each metadata record has its own file assigned, instead sharing the
 * default one, as would be the cores standard behaviour.
 *
 * Class MetaDataRecordsUpdateWizard
 */
class MetaDataRecordsUpdateWizard extends AbstractUpdate
{
    protected $title = 'Prepare Instance for translateable files.';

    /**
     * Checks whether updates are required.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $execute = false;

        // check for existing sys_file_metadata records in sys_language_uid > 0

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $translatedMetadataRecords = $queryBuilder->count('uid')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->gt('sys_language_uid', 0)
        )->execute()->fetchColumn();

        // check for sys_file records in sys_language_uid > 0
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        $translatedFileRecords = $queryBuilder->count('uid')->from('sys_file')->where(
            $queryBuilder->expr()->gt('sys_language_uid', 0)
        )->execute()->fetchColumn();

        if ($translatedMetadataRecords > 0 && ($translatedMetadataRecords > $translatedFileRecords)) {
            $execute = true;
        }

        if ($execute === true) {
            $description = 'Core default behaviour demands for each translation of a sys_file_metadata record to refer to a'
            . 'single sys_file record, that in turn features the same physical image.';
        }
        return $execute;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
    {
        /** @var ResourcesService $resourcesService */
        $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
        $folder = $resourcesService->prepareFileStorageEnvironment();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $translatedFileMetadataRecords = $queryBuilder->select('*')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->gt('sys_language_uid', 0)
        )->execute();
        while ($metaDataRecord = $translatedFileMetadataRecords->fetch()) {
            $resourcesService->copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy(
                $metaDataRecord['sys_language_uid'],
                $metaDataRecord,
                $folder
            );
        }

        return true;
    }
}
