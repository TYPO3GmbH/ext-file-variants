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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bundles all requests to the database
 *
 * helper for unit tests
 *
 * Class PersistenceService
 * @package T3G\AgencyPack\FileVariants\DataHandler
 * @internal
 */
class PersistenceService
{

    /**
     * @param int $l10n_parent
     * @param int $sys_language_uid
     * @return array
     */
    public function getSysFileMetaDataRecord(int $l10n_parent, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->select('*')->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($l10n_parent, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)
                )
            );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param int $parentUid
     * @param int $sys_language_uid
     * @return array
     */
    public function getSysFileRecord(int $parentUid, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->select('*')->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($parentUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)
                )
            );
        $result = $queryBuilder->execute()->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param $cmdMap
     */
    public function process_cmdMap($cmdMap)
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdMap);
        $dataHandler->process_cmdmap();
    }

    /**
     * @param $dataMap
     */
    public function process_dataMap($dataMap)
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param int $fileUid
     * @param int $sys_language_uid
     * @return array
     */
    public function collectAffectedReferences(int $fileUid, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('uid')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
        );
        return $queryBuilder->execute()->fetchAll();

    }

    /**
     * @param array $references
     * @param int $newFileUid
     */
    public function updateReferences(array $references, int $newFileUid)
    {
        $change = ['uid_local' => $newFileUid];
        $dataMap = ['sys_file_reference' => []];
        foreach ($references as $reference) {
            $dataMap['sys_file_reference'][$reference] = $change;
        }
        $this->process_dataMap($dataMap);
    }

    /**
     * @param int $uid
     * @return array
     */
    public function getSysFileReferenceRecord(int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('*')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param string $table
     * @param int $uid
     * @return array
     */
    public function getRecord(string $table, int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );

        $result = $queryBuilder->execute()->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $metadataUid
     * @return array
     */
    public function getSysFileMetaDataRecordByUid(int $metadataUid): array
    {
         /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->select('*')->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($metadataUid, \PDO::PARAM_INT)
                )
            );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param $storageName
     * @return int
     */
    public function getFileStorageRecordUid(string $storageName): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
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

    /**
     * @param int $uid
     * @return File
     */
    public function getFileObject(int $uid): File
    {
        return ResourceFactory::getInstance()->getFileObject($uid);
    }

    /**
     * @return Folder
     */
    public function findStorageDestination(): Folder
    {
        $fileStorageRecordUid = $this->getFileStorageRecordUid('language_variants');

        if ($fileStorageRecordUid > 0) {
            $storage = ResourceFactory::getInstance()->getStorageObject($fileStorageRecordUid);
        } else {
            $storage = ResourceFactory::getInstance()->getDefaultStorage();
        }
        return $this->ensureAvailableFolder($storage);
    }

    /**
     * @param ResourceStorage $storage
     * @return Folder
     */
    protected function ensureAvailableFolder(ResourceStorage $storage): Folder
    {
        $folder = null;
        $folderName = 'languageVariants';
        if ($storage->hasFolder($folderName) === false) {
            $folder = $storage->createFolder($folderName);
        } else {
            $folder = $storage->getFolder($folderName);
        }
        return $folder;
    }

    /**
     * @param File $parentFile
     * @param Folder $folder
     * @return File
     */
    public function copyFileObject($parentFile, $folder): File
    {
        return $parentFile->copyTo($folder);
    }

    /**
     * @param int $fileUid
     * @param string $localFilePath
     */
    public function replaceFile(int $fileUid, string $filename, string $localFilePath)
    {
        $file = ResourceFactory::getInstance()->getFileObject($fileUid);
        $file->getStorage()->replaceFile($file, $localFilePath);
        $file->rename($filename);
    }

    /**
     * @param int $id
     */
    public function emptyLanguageVariantsField(int $id)
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('no metadata uid given', 1489398161);
        }
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->update('sys_file_metadata')->set('language_variant', '')->where(
            $queryBuilder->expr()->eq(
                'uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
            )
        )->execute();

    }

    /**
     * @param string $table
     * @param int $id
     * @param int $sys_language_uid
     * @return array
     */
    public function getTranslatedRecord(string $table, int $id, int $sys_language_uid): array
    {
        if ($id < 1) {
            throw new \InvalidArgumentException('no valid uid given', 1489497807);
        }

        if ($sys_language_uid < 1) {
            throw new \InvalidArgumentException('no valid language uid given', 1489497808);
        }
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        // get rid of all but deleted constraint, we need to update disabled record references as well
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['ctrl']['languageField'], $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        );
        $result = $queryBuilder->execute()->fetch();
        return is_array($result) ? $result : [];
    }

    /**
     * @param int $uid_foreign
     * @param int $sys_language_uid
     * @param string $tableName
     * @return array
     */
    public function findReferencesByUidForeignAndSysLanguageUid(int $uid_foreign, int $sys_language_uid, string $tableName)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('*')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($uid_foreign, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter($tableName))
        );
        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * @param string $table
     * @param int $uid
     */
    public function deleteRecord(string $table, int $uid)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->delete($table)->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        )->execute();

    }
}
