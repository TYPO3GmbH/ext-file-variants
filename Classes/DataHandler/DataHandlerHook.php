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
     * @param FileRecordService $fileRecordService
     * @param ReferenceRecordService $referenceRecordService
     * @param PersistenceService $persistenceService
     */
    protected function initializeServices($fileRecordService = null, $referenceRecordService = null, $persistenceService = null)
    {
        $this->persistenceService = $persistenceService;
        if ($this->persistenceService === null) {
            $this->persistenceService = GeneralUtility::makeInstance(PersistenceService::class);
        }
        $this->referenceRecordService = $referenceRecordService;
        if ($this->referenceRecordService === null) {
            $this->referenceRecordService = GeneralUtility::makeInstance(ReferenceRecordService::class, $this->persistenceService);
        }
        $this->fileRecordService = $fileRecordService;
        if ($this->fileRecordService === null) {
            $this->fileRecordService = GeneralUtility::makeInstance(FileRecordService::class, $this->persistenceService);
        }
    }

    /**
     *
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     * @param FileRecordService $fileRecordService
     * @param ReferenceRecordService $referenceRecordService
     * @param PersistenceService $persistenceService
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler $pObj,
        $fileRecordService = null,
        $referenceRecordService = null,
        $persistenceService = null
    )
    {

        $this->initializeServices($fileRecordService, $referenceRecordService, $persistenceService);

        // sys_file_metadata record is updated with file_variant set
        // related file must be replaced (preserve the uid)!
        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('file_variant', $fieldArray)) {
            $this->fileRecordService->updateSysFileRecord();
        }

    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id recordUid
     * @param mixed $value Command Value
     * @param DataHandler $pObj
     * @param $pasteUpdate
     * @param array $pasteDatamap
     * @param FileRecordService $fileRecordService
     * @param ReferenceRecordService $referenceRecordService
     * @param PersistenceService $persistenceService
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value,
        DataHandler $pObj,
        $pasteUpdate,
        array $pasteDatamap,
        $fileRecordService = null,
        $referenceRecordService = null,
        $persistenceService = null
    )
    {

        $this->initializeServices($fileRecordService, $referenceRecordService, $persistenceService);

        // translation of metadata record
        // results in copied sys_file and relation of record to new file
        // all references need to be updated to the new file
        if ($table === 'sys_file_metadata' && $command === 'localize') {

            if (is_string($id) && strpos($id, 'NEW') >= 0) {
                $id = $pObj->substNEWwithIDs[$id];
            }
            if ((int)$id === 0) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332067);
            }
            $handledMetaDataRecord = $this->persistenceService->getSysFileMetaDataRecord((int)$id, (int)$value);
            $fileUid = (int)$handledMetaDataRecord['file'];
            $translatedFileUid = $this->fileRecordService->copySysFileRecord($fileUid, $value);
            $this->fileRecordService->updateSysFileMetadata($handledMetaDataRecord['uid'], $translatedFileUid);
            $this->referenceRecordService->updateReferences($fileUid, $translatedFileUid, $value);

        }

        // if a consuming table receives the localize command, check the references for available variants

    }

}
