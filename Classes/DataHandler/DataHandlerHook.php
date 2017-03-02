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
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray)
    {
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
        $changeData = [];
        $variantFileUid = $file->getUid();
        $changeData['sys_file'][$variantFileUid] = [
            'sys_language_uid' => $sys_language_uid,
            'l10n_parent' => $currentFileUid
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($changeData, []);
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
        $changeData = [];
        $changeData['sys_file_metadata'][$id] = [
            'file' => $fileUid,
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($changeData, []);
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
