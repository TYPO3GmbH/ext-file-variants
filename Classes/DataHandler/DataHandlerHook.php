<?php
declare(strict_types = 1);
namespace T3G\AgencyPack\FileVariants\DataHandler;

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
use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Description
 */
class DataHandlerHook
{

    /**
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler &$pObj
    ) {


        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('language_variant',
                $fieldArray)
        ) {
            // move file into fileadmin and create file object
            $filePath = $this->calculateFullPathToUploadedFile($fieldArray['language_variant']);

            $file = null;
            if (file_exists($filePath) && is_file($filePath)) {
                $folder = $this->findStorageDestination();
                $file = $folder->addFile($filePath, '', DuplicationBehavior::RENAME);
            }
            if ($file instanceof File && $file->getUid() > 0) {
                $sys_language_uid = $this->retrieveCurrentSysLanguageUid((int)$id);
                $currentFileUid = $this->retrieveCurrentSysFile((int)$id);

                // the just created file and metadata for that file need to be adjusted
                //$defaultLanguageFile =
                //$fileVariantFile =
            }
            // $fileVariantMetadata =
        }
    }

    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler &$pObj,
        $pasteUpdate,
        array $pasteDatamap
    ) {
        //DebuggerUtility::var_dump($table, $command, 8, true);

    }

    /**
     * @param string $fileName
     * @return string filePath
     */
    protected function calculateFullPathToUploadedFile(string $fileName): string
    {
        $sourceFolder = PATH_site . 'uploads/tx_filevariants/';
        if (strpos($fileName, ',') !== false) {
            $fileName = substr($fileName, strrpos($fileName, ',') + 1);
        }
        return $sourceFolder . $fileName;
    }

    /**
     * @return Folder
     */
    protected function findStorageDestination(): Folder
    {
        $folder = null;
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
        $customStorage = $queryBuilder->select('*')->from('sys_file_storage')
            ->where(
                $queryBuilder->expr()->eq(
                    'name', $queryBuilder->createNamedParameter('language_variants')
                ),
                $queryBuilder->expr()->eq(
                    'is_online',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'is_writable',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )->execute()->fetchAll();
        if ($customStorage[0]['uid'] > 0) {
            $storage = ResourceFactory::getInstance()->getStorageObject($customStorage[0]['uid']);
        } else {
            $storage = ResourceFactory::getInstance()->getDefaultStorage();
        }

        $folder = $this->ensureAvailableFolder($storage);
        return $folder;
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
            $folder = $storage->createFolder($folderName, $storage->getFolder('/'));
        } else {
            $folder = $storage->getFolder($folderName);
        }
        return $folder;
    }

    /**
     * @param int $id
     * @return int $sys_language_uid
     */
    protected function retrieveCurrentSysLanguageUid(int $id): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $metaDataRecord = $queryBuilder->select('*')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchAll();

        return (int)$metaDataRecord[0]['sys_language_uid'];
    }

    /**
     * @param int $id
     * @return int $file_uid
     */
    protected function retrieveCurrentSysFile(int $id): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $metaDataRecord = $queryBuilder->select('*')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchAll();

        return (int)$metaDataRecord[0]['file'];
    }

}
