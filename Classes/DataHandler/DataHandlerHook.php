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
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\RecordService;
use T3G\AgencyPack\FileVariants\Service\ReferenceRecordService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description
 */
class DataHandlerHook
{
    /**
     * @var FileRecordService
     */
    protected $fileRecordService;

    /**
     * @var ReferenceRecordService
     */
    protected $referenceRecordService;

    /**
     * @var PersistenceService;
     */
    protected $persistenceService;

    /**
     * @var RecordService
     */
    protected $recordService;

    /**
     * @param FileRecordService $fileRecordService
     * @param ReferenceRecordService $referenceRecordService
     * @param PersistenceService $persistenceService
     * @param RecordService $recordService
     */
    public function __construct($fileRecordService = null, $referenceRecordService = null, $persistenceService = null, $recordService = null)
    {
        $this->persistenceService = $persistenceService;
        if ($this->persistenceService === null) {
            $this->persistenceService = GeneralUtility::makeInstance(PersistenceService::class);
        }
        $this->recordService = $recordService;
        if ($this->recordService === null) {
            $this->recordService = GeneralUtility::makeInstance(RecordService::class, $this->persistenceService);
        }
        $this->referenceRecordService = $referenceRecordService;
        if ($this->referenceRecordService === null) {
            $this->referenceRecordService = GeneralUtility::makeInstance(ReferenceRecordService::class, $this->persistenceService, $this->recordService);
        }
        $this->fileRecordService = $fileRecordService;
        if ($this->fileRecordService === null) {
            $this->fileRecordService = GeneralUtility::makeInstance(FileRecordService::class, $this->persistenceService, $this->referenceRecordService);
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
        // sys_file_metadata record is updated with file_variant set
        // related file must be replaced (preserve the uid)!
        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('language_variant', $fieldArray)) {
            $id = $this->substNewWithId($id, $pObj);
            $this->fileRecordService->replaceFileContentOfRelatedFile($id, $fieldArray['language_variant']);
            $this->persistenceService->emptyLanguageVariantsField($id);
        }
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
        // translation of metadata record
        // results in copied sys_file and relation of record to new file
        // all references need to be updated to the new file
        if ($table === 'sys_file_metadata' && $command === 'localize') {

            $id = $this->substNewWithId($id, $pObj);
            if ($id < 1) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332067);
            }
            $handledMetaDataRecord = $this->persistenceService->getSysFileMetaDataRecord((int)$id, (int)$value);
            $fileUid = (int)$handledMetaDataRecord['file'];
            $translatedFileUid = $this->fileRecordService->translateSysFileRecord($fileUid, (int)$value);
            $this->fileRecordService->updateSysFileMetadata($handledMetaDataRecord['uid'], $translatedFileUid);
            $this->referenceRecordService->updateReferences($fileUid, $translatedFileUid, (int)$value);
        }

        // if a consuming table receives the localize command, check the references for available variants
        if ($command === 'localize' && $this->recordService->isFalConsumingTable($table)) {
            $id = $this->substNewWithId($id, $pObj);
            if ($id < 1) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332068);
            }
            $this->fileRecordService->adjustTranslatedReferencesToFileVariants($table, $id, $value);
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

}
