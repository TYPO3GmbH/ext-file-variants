<?php
declare(strict_types=1);

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class DataHandlerHook
{
    /**
     * @var string
     */
    protected $uploadFolderPath = PATH_site . 'typo3temp/file_variants_uploads';

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorageInterface
     */
    protected $storage;

    /**
     * @var \TYPO3\CMS\Core\Resource\FolderInterface
     */
    protected $folder;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'])) {
            throw new \RuntimeException('No extension configuration found. Go to ExtensionManager and press the wheel symbol for ext:file_variants.', 1490476773);
        }
        if (!is_dir($this->uploadFolderPath)) {
            mkdir($this->uploadFolderPath, 2777, true);
        }
    }

    /**
     *
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler $pObj
    )
    {

    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id recordUid
     * @param mixed $value Command Value
     * @param DataHandler $pObj
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler $pObj
    )
    {
        if ($table !== 'sys_file_storage') {
            $this->prepareFileStorageEnvironment();
        }

        // translation of metadata record
        // results in copied sys_file and relation of record to new file
        // all references need to be updated to the new file
        if ($table === 'sys_file_metadata' && $command === 'localize') {

            $id = $this->substNewWithId($id, $pObj);
            if ($id < 1) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332067);
            }

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->select('uid', 'file')->from('sys_file_metadata')
                ->where(
                    $queryBuilder->expr()->eq(
                        'l10n_parent',
                        $queryBuilder->createNamedParameter((int)$id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter((int)$value, \PDO::PARAM_INT)
                    )
                );
            $handledMetaDataRecord = $queryBuilder->execute()->fetch();

            $fileUid = (int)$handledMetaDataRecord['file'];
            $parentFile = ResourceFactory::getInstance()->getFileObject($fileUid);

            $copy = $parentFile->copyTo($this->folder);
            $translatedFileUid = $copy->getUid();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $queryBuilder->update('sys_file')->set('sys_language_uid', (int)$value)->set('l10n_parent', $fileUid)->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($translatedFileUid, \PDO::PARAM_INT))
            )->execute();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->update('sys_file_metadata')->set('file', $translatedFileUid)->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$handledMetaDataRecord['uid'], \PDO::PARAM_INT))
            )->execute();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->select('uid')->from('sys_file_reference')->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter((int)$value, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            );
            $references = $queryBuilder->execute()->fetchAll();
            $filteredReferences = [];
            foreach ($references as $reference) {
                $uid = $reference['uid'];
                if ($this->isValidReference($uid)) {
                    $filteredReferences[] = $uid;
                }
            }
            foreach ($filteredReferences as $reference) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                $queryBuilder->update('sys_file_reference')->set('file', $fileUid)->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($reference, \PDO::PARAM_INT))
                )->execute();
            }
        }


    }

    /**
     * make sure upload storage and folder are in place
     */
    protected function prepareFileStorageEnvironment()
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants']);
        $storageUid = (int)$extensionConfiguration['variantsStorageUid'];
        $targetFolder = $extensionConfiguration['variantsFolder'];
        try {
            $this->storage = ResourceFactory::getInstance()->getStorageObject($storageUid);

            if (!$this->storage->hasFolder($targetFolder)) {
                $this->folder = $this->storage->createFolder($targetFolder);
            } else {
                $this->folder = $this->storage->getFolder($targetFolder);
            }
        } catch (\InvalidArgumentException $exception) {
            throw new \RuntimeException('storage with uid ' . $storageUid . ' is not available. Create it and check the given uid in extension configuration.', 1490480372);
        }
    }

    /**
     * @param string|int $id
     * @param DataHandler $pObj
     * @return int
     */
    protected function substNewWithId($id, DataHandler $pObj): int
    {
        if (is_string($id) && strpos($id, 'NEW') >= 0) {
            $id = $pObj->substNEWwithIDs[$id];
        }
        if ($id === null) {
            $id = -1;
        }
        return $id;
    }

    /**
     * Filters away irrelevant tables and checks for free mode in tt_content records
     * everything else is a valid reference in context of file variants update
     *
     * @param int $uid
     * @return bool
     */
    protected function isValidReference(int $uid): bool
    {
        $isValid = true;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('tablenames', 'uid_foreign')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );
        $sysFileReferenceRecord = $queryBuilder->execute()->fetch();
        $irrelevantTableNames = ['pages', 'pages_language_overlay', 'sys_file_metadata', 'sys_file'];
        if (in_array($sysFileReferenceRecord['tablenames'], $irrelevantTableNames)) {
            $isValid = false;
        }
        return $isValid;
    }
}
