<?php
declare(strict_types = 1);
namespace T3G\AgencyPack\FileVariants\Service;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to wrap all kind of database queries
 */
class PersistenceService
{

    ######################################
    # sys_file_storage
    ######################################
    /**
     * @param $storageName
     * @return int
     */
    public function getFileStorageRecordUid(string $storageName): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_storage');
        return (int)$queryBuilder->select('uid')->from('sys_file_storage')
            ->where(
                $queryBuilder->expr()->eq(
                    'name', $queryBuilder->createNamedParameter($storageName)
                ),
                $queryBuilder->expr()->eq(
                    'is_online',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'is_writable',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )->execute()->fetchColumn();
    }

    ######################################
    # sys_file_metadata
    ######################################
    /**
     * @param int $fileUid
     * @param int $sys_language_uid
     * @return int $fileUid
     */
    public function getMetadataForFileAndLanguage(int $fileUid, int $sys_language_uid = 0): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        return (int)$queryBuilder->select('uid')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('file',
                $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('sys_language_uid',
                $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT))
        )->execute()->fetchColumn();
    }

    /**
     * While using DataHandler to empty the field, it tries to delete the linked file
     * this is already moved, so the operation fails. Hard removing the field value is needed here.
     *
     * @param int $id
     */
    public function removeFileVariantStringFromRecord(int $id)
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->update('sys_file_metadata')
            ->set('language_variant', '')
            ->where(
                $queryBuilder->expr()->eq('uid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )->execute();
    }

    /**
     * @param int $id
     * @return int
     */
    public function getFileUidFromSysFileMetadataByUid(int $id): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        $currentFileUid = (int)$queryBuilder->select('file')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchColumn();
        return $currentFileUid;
    }

    /**
     * @param int $id
     * @return int $file_uid
     */
    public function getSysFileFromSysFileMetadataLanguageParent(int $id): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        $metadataParent = (int)$queryBuilder->select('l10n_parent')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchColumn();

        return $this->getFileUidFromSysFileMetadataByUid($metadataParent);
    }

    /**
     * @param int $id
     * @return int $sys_language_uid
     */
    public function retrieveCurrentSysLanguageUid(int $id): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        return (int)$queryBuilder->select('sys_language_uid')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchAll();
    }

    /**
     * @param $variantMetaDataUid
     */
    public function deleteSysFileMetadataRecord($variantMetaDataUid)
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->delete('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq('uid',
                    $queryBuilder->createNamedParameter($variantMetaDataUid, \PDO::PARAM_INT))
            )->execute();
    }

    ######################################
    # sys_file_reference
    ######################################

    /**
     * @param string $table
     * @param $id
     * @param $falField
     * @param $sys_language_uid
     * @return array
     */
    public function getReferencesUsingThisFile(string $table, $id, $falField, $sys_language_uid): array
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file_reference');
        return $queryBuilder->select('uid', 'uid_local')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid_foreign',
                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['columns'][$falField]['config']['foreign_table_field'],
                $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('fieldname',
                $queryBuilder->createNamedParameter($GLOBALS['TCA'][$table]['columns'][$falField]['config']['foreign_match_fields']['fieldname'],
                    \PDO::PARAM_STR)),
            $queryBuilder->expr()->eq('sys_language_uid',
                $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT))
        )->execute()->fetchAll();
    }

    ######################################
    # sys_file
    ######################################
    /**
     * @param $referencedFile
     * @return int
     */
    public function getFileRecordLanguage(int $referencedFile): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder->select('sys_language_uid')->from('sys_file')->where(
            $queryBuilder->expr()->eq('uid',
                $queryBuilder->createNamedParameter($referencedFile, \PDO::PARAM_INT))
        )->execute()->fetchColumn();
    }

    /**
     * @param $sys_language_uid
     * @param $fileUid
     * @return int
     */
    public function getLanguageParentFile(int $sys_language_uid, int $fileUid): int
    {
        $queryBuilder = $this->getQueryBuilderForTable('sys_file');
        return (int)$queryBuilder->select('uid')->from('sys_file')->where(
            $queryBuilder->expr()->eq('sys_language_uid',
                $queryBuilder->createNamedParameter($sys_language_uid,
                    \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('l10n_parent',
                $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
        )->execute()->fetchColumn();
    }

    ######################################
    # consuming table
    ######################################
    /**
     * @param string $table
     * @param int|string $id
     * @param array $selectFields
     * @return array
     */
    public function getFalConsumingRecord(
        string $table,
        int $id,
        array $selectFields
    ): array {
        $queryBuilder = $this->getQueryBuilderForTable($table);
        // the record might be set disabled in this operation, then we still need the records
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select(...$selectFields)->from($table)->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetch();
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilderForTable($table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

}
