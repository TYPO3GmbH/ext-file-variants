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
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Looks after translated filemetadata records and copies the related files into
 * translation storage, if not done yet.
 * Makes sure each metadata record has its own file assigned, instead sharing the
 * default one, as would be the cores standard behaviour.
 *
 * Class MetaDataRecordsUpdateWizard
 */
class MetaDataRecordsUpdateWizard implements UpgradeWizardInterface
{
    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Prepare Instance for translateable files.';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Returns an array of class names of Prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $execute = false;

        // check for existing sys_file_metadata records in sys_language_uid > 0

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $translatedMetadataRecords = $queryBuilder->count('uid')->from('sys_file_metadata')->where($queryBuilder->expr()->gt('sys_language_uid', 0))->executeQuery()->fetchOne();

        // check for sys_file records in sys_language_uid > 0
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        $translatedFileRecords = $queryBuilder->count('uid')->from('sys_file')->where($queryBuilder->expr()->gt('sys_language_uid', 0))->executeQuery()->fetchOne();

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
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        /** @var ResourcesService $resourcesService */
        $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
        $folder = $resourcesService->prepareFileStorageEnvironment();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $translatedFileMetadataRecords = $queryBuilder->select('*')->from('sys_file_metadata')->where($queryBuilder->expr()->gt('sys_language_uid', 0))->executeQuery();

        while ($metaDataRecord = $translatedFileMetadataRecords->fetchAssociative()) {
            $resourcesService->copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy(
                $metaDataRecord['sys_language_uid'],
                $metaDataRecord,
                $folder
            );
        }

        return true;
    }
}
