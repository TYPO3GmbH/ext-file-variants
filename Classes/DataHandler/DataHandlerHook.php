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
use T3G\AgencyPack\FileVariants\Service\FileHandlingService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description
 */
class DataHandlerHook
{

    /**
     * used to remove unusable information from metadata record
     * (after handling, the uploaded file is not there anymore)
     *
     * @param string $status
     * @param string $table
     * @param $id
     * @param array $fieldArray
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, DataHandler $pObj)
    {

        // [AL 1-2] a translated sys_file record comes in - this results from a localize sys_file_metadata action.
        // see @[AL 1-1]
        // the file needs to be related to the language record of that metadata record (yet unknown) and the
        // files own metadata record (if exists) needs to be removed.
        if ($table === 'sys_file' && $status === 'new' && isset($fieldArray['sys_language_uid']) && $fieldArray['sys_language_uid'] > 0) {

            // get localized metadata record from language parent of this file
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $parentFileMetadataRecordUid = (int)$queryBuilder->select('uid')->from('sys_file_metadata')->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fieldArray['l10n_parent'], \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($fieldArray['sys_language_uid'], \PDO::PARAM_INT))
            )->execute()->fetchColumn();

            // relate that new file record to metadata translation
            $fileUid = $pObj->substNEWwithIDs[$id];
            $datamap = [
                'sys_file_metadata' => [
                    $parentFileMetadataRecordUid => [
                        'file' => $fileUid,
                    ],
                ],
            ];
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($datamap, []);
            $dataHandler->process_datamap();

            // remove the metadata record for the translated file, if there is any
            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $metaDataRecordToDelete = $queryBuilder->select('uid')->from('sys_file_metadata')->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )->execute()->fetchColumn();

            if ((int)$metaDataRecordToDelete > 0) {
                $commandMap = [
                    'sys_file_metadata' => [
                        $metaDataRecordToDelete => 'delete',
                    ],
                ];
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $commandMap);
                $dataHandler->process_cmdmap();
            }

        }

        // [AL 2-1] a metadata record receives a new file to be used as language variant
        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('language_variant',
                $fieldArray) && ((int)$fieldArray['language_variant'] !== '') && !array_key_exists('file', $fieldArray)
        ) {
            /** @var FileHandlingService $fileHandlingService */
            $fileHandlingService = GeneralUtility::makeInstance(FileHandlingService::class);

            $file = $fileHandlingService->moveUploadedFileAndCreateFileObject($fieldArray['language_variant']);

            if ($file instanceof File && $file->getUid() > 0) {
                $sys_language_uid = $this->retrieveCurrentSysLanguageUid((int)$id);
                $currentFileUid = $this->retrieveCurrentSysFile((int)$id);
                $this->updateFileVariantRecordsWithLanguageInformation($file, $sys_language_uid, $currentFileUid);
                $this->updateMetadataWithFileVariant((int)$id, (int)$file->getUid());
            }
            $this->removeFileVariantStringFromRecord((int)$id);
        }
    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id recordUid
     * @param mixed $value Command Value
     * @param DataHandler $pObj
     * @param mixed $pasteUpdate
     * @param array $pasteDatamap
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler &$pObj,
        $pasteUpdate,
        array $pasteDatamap
    ) {
        if ($table === 'sys_file_metadata' && $command === 'localize') {

            // get the complete record for processed uid
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $metadata = $queryBuilder->select('file', 'sys_language_uid')->from($table)->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )->execute()->fetch();

            // localize the related file record from language parent
            $fileUid = $metadata['file'];
            $commandMap = [
                'sys_file' => [
                    $fileUid => [
                        'localize' => $value
                    ],
                ],
            ];
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $commandMap);
            $dataHandler->process_cmdmap();

            // [AL 1-1] this leads to a new process_datamap call in DataHandler, that is again hooked into.
            // see @[AL 1-2]
            // the resulting new file record is related with the newly created metadata record.
        }
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
        $metadataParent = (int)$queryBuilder->select('l10n_parent')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchColumn();


        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $file = $queryBuilder->select('*')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($metadataParent, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
        )->execute()->fetch();

        return (int)$file['file'];
    }

    /**
     * @param File $file
     * @param int $sys_language_uid
     * @param int $currentFileUid
     */
    protected function updateFileVariantRecordsWithLanguageInformation(
        File $file,
        int $sys_language_uid,
        int $currentFileUid
    ) {
        $variantFileUid = $file->getUid();
        $dataMap['sys_file'][$variantFileUid] = [
            'sys_language_uid' => $sys_language_uid,
            'l10n_parent' => $currentFileUid
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        // this record is not needed, because the variant file is bound to the translated metadata record of the original file.
        $variantMetaData = $file->_getMetaData();
        if (isset($variantMetaData['uid']) && $variantMetaData['uid'] > 0) {
            $variantMetaDataUid = $variantMetaData['uid'];

            /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->delete('sys_file_metadata')
                ->where(
                    $queryBuilder->expr()->eq('uid',
                        $queryBuilder->createNamedParameter($variantMetaDataUid, \PDO::PARAM_INT))
                )->execute();
        }
    }

    /**
     * @param int $id
     * @param int $fileUid
     */
    protected function updateMetadataWithFileVariant(int $id, int $fileUid)
    {
        // delete the currently related file record
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $currentFileUid = (int)$queryBuilder->select('file')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
        )->execute()->fetchColumn();

        $commandMap = [];
        $commandMap['sys_file'][$currentFileUid] = ['delete' => true];

        $dataMap = [];
        $dataMap['sys_file_metadata'][$id] = [
            'file' => $fileUid,
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_cmdmap();
        $dataHandler->process_datamap();
    }

    /**
     * While using DataHandler to empty the field, it tries to delete the linked file
     * this is already moved, so the operation fails. Hard removing the field value is needed here.
     *
     * @param int $id
     */
    protected function removeFileVariantStringFromRecord(int $id)
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->update('sys_file_metadata')
            ->set('language_variant', '')
            ->where(
                $queryBuilder->expr()->eq('uid',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )->execute();
    }

}
