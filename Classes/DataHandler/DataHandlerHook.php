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
use T3G\AgencyPack\FileVariants\Service\FileHandlingService;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description
 */
class DataHandlerHook
{
    /**
     * @var FileHandlingService
     */
    protected $fileHandlingService;

    /**
     * @var PersistenceService
     */
    protected $persistenceService;

    /**
     * @param FileHandlingService|null $fileHandlingService
     * @param PersistenceService|null $persistenceService
     */
    public function initializeServices(
        $fileHandlingService = null,
        $persistenceService = null
    ) {
        $this->fileHandlingService = $fileHandlingService;
        if ($this->fileHandlingService === null) {
            $this->fileHandlingService = GeneralUtility::makeInstance(FileHandlingService::class);
        }
        $this->persistenceService = $persistenceService;
        if ($this->persistenceService === null) {
            $this->persistenceService = GeneralUtility::makeInstance(PersistenceService::class);
        }
    }

    /**
     * used to remove unusable information from metadata record
     * (after handling, the uploaded file is not there anymore)
     *
     * @param string $status
     * @param string $table
     * @param $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler $pObj
    ) {

        $this->initializeServices();

        // [AL 1-2] a translated sys_file record comes in - this results from a localize sys_file_metadata action.
        // see @[AL 1-1]
        // the file needs to be related to the language record of that metadata record (yet unknown) and the
        // files own metadata record (if exists) needs to be removed.
        if ($table === 'sys_file' && $status === 'new' && isset($fieldArray['sys_language_uid']) && $fieldArray['sys_language_uid'] > 0) {
            $this->linkTranslatedFileToTranslatedSysFileMetadataRecord($id, $fieldArray, $pObj);
        }

        // [AL 2-1] a metadata record receives a new file to be used as language variant
        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('language_variant',
                $fieldArray) && ((int)$fieldArray['language_variant'] !== '') && !array_key_exists('file', $fieldArray)
        ) {
            /** @var FileHandlingService $fileHandlingService */
            $fileHandlingService = GeneralUtility::makeInstance(FileHandlingService::class);

            $file = $fileHandlingService->moveUploadedFileAndCreateFileObject($fieldArray['language_variant']);

            if ($file instanceof File && $file->getUid() > 0) {
                $sys_language_uid = $this->persistenceService->retrieveCurrentSysLanguageUid((int)$id);
                $currentFileUid = $this->persistenceService->getSysFileFromSysFileMetadataLanguageParent((int)$id);
                $this->updateFileVariantRecordsWithLanguageInformation($file, $sys_language_uid, $currentFileUid);
                $this->updateMetadataWithFileVariant((int)$id, (int)$file->getUid());
            }
            $this->persistenceService->removeFileVariantStringFromRecord((int)$id);
        }

        // [AL 3-1] a consuming table record (FAL, connected mode) gets translated
        // if a language variant for any referenced file exists, the reference record needs to link to that variant file record
        // if no variant exists, the record links to the default file

        // forget about sys_file_reference and pages here
        if ($table !== 'sys_file_reference' && $table !== 'pages' && $table !== 'pages_language_overlay') {
            $this->relateTranslatedFileToTranslatedSysFileReference($table, $id, $pObj);
        }
    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id recordUid
     * @param mixed $value Command Value
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value
    ) {
        $this->initializeServices();

        if ($table === 'sys_file_metadata' && $command === 'localize') {

            // get the complete record for processed uid
            $metadata = $this->persistenceService->getFalConsumingRecord($table, (int)$id, ['file', 'sys_language_uid']);

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
            $this->persistenceService->deleteSysFileMetadataRecord($variantMetaDataUid);
        }
    }

    /**
     * @param int $id
     * @param int $fileUid
     */
    protected function updateMetadataWithFileVariant(int $id, int $fileUid)
    {
        $currentFileUid = $this->persistenceService->getFileUidFromSysFileMetadataByUid($id);

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
     * @param string|int $id this can be a uid or a NEW<uid> placeholder.
     * @param array $fieldArray
     * @param DataHandler $pObj
     * @return array
     */
    protected function linkTranslatedFileToTranslatedSysFileMetadataRecord(
        $id,
        array $fieldArray,
        DataHandler $pObj
    ) {
        // get localized metadata record from language parent of this file
        $parentFileMetadataRecordUid = $this->persistenceService->getMetadataForFileAndLanguage((int)$fieldArray['l10n_parent'],
            (int)$fieldArray['sys_language_uid']);

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
        $metaDataRecordToDelete = $this->persistenceService->getMetadataForFileAndLanguage((int)$fileUid);


        if ($metaDataRecordToDelete > 0) {
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

    /**
     * @param string $table
     * @param $id
     * @param DataHandler $pObj
     */
    protected function relateTranslatedFileToTranslatedSysFileReference(string $table, $id, DataHandler $pObj)
    {
        // find out, whether there is a FAL field in this table
        $tcaColumns = $GLOBALS['TCA'][$table]['columns'];
        $tableFalFields = [];
        foreach ($tcaColumns as $fieldName => $fieldConfig) {
            if ($fieldConfig['config']['foreign_table'] === 'sys_file_reference') {
                $tableFalFields[] = $fieldName;
            }
        }

        // find out, whether this record is sys_language_uid > 0 and connected mode
        $languageFieldName = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $languageParentFieldName = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        // is the table translatable at all?
        if (isset($languageFieldName, $languageParentFieldName)) {
            if (isset($pObj->substNEWwithIDs[$id])) {
                $id = $pObj->substNEWwithIDs[$id];
            }
            $selectFields = $tableFalFields;
            $selectFields[] = $languageFieldName;
            $selectFields[] = $languageParentFieldName;
            $record = $this->persistenceService->getFalConsumingRecord($table, (int)$id, $selectFields);

            $sys_language_uid = $record[$languageFieldName];
            // if free mode, no parent is set. Ignore
            if (isset($sys_language_uid) && $sys_language_uid > 0 && $record[$languageParentFieldName] > 0) {
                // get the references
                foreach ($tableFalFields as $falField) {
                    $this->checkFalFieldsInRecordForNeededUpdate($table, $id, $falField, $sys_language_uid);
                }
            }
        }
    }

    /**
     * @param $sys_language_uid
     * @param $referencedFile
     * @param $referenceUid
     */
    protected function linkTranslatedFileToSysFileReference(
        $sys_language_uid,
        $referencedFile,
        $referenceUid
    ) {
        $translatedFileUid = $this->persistenceService->getLanguageParentFile($sys_language_uid, $referencedFile);

        if ($translatedFileUid > 0) {
            $datamap = [
                'sys_file_reference' => [
                    $referenceUid => [
                        'uid_local' => $translatedFileUid
                    ]
                ],
            ];
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($datamap, []);
            $dataHandler->process_datamap();
        }
    }

    /**
     * @param $reference
     * @param $sys_language_uid
     */
    protected function updateSysFileReference($reference, $sys_language_uid)
    {
        $referencedFile = (int)$reference['uid_local'];
        if (isset($referencedFile) && $referencedFile > 0) {
            $referenceUid = $reference['uid'];
            // check the file and its variants
            $fileLanguage = $this->persistenceService->getFileRecordLanguage($referencedFile);

            if (isset($fileLanguage) && (int)$fileLanguage !== $sys_language_uid) {
                $this->linkTranslatedFileToSysFileReference($sys_language_uid,
                    $referencedFile, $referenceUid);
            }
        }
    }

    /**
     * @param string $table
     * @param $id
     * @param $falField
     * @param $sys_language_uid
     */
    protected function checkFalFieldsInRecordForNeededUpdate(string $table, $id, $falField, $sys_language_uid)
    {
        // I will not rely on the refindex here. Just check the references table
        $references = $this->persistenceService->getReferencesUsingThisFile($table, $id, $falField, $sys_language_uid);

        if (count($references) > 0) {
            foreach ($references as $reference) {
                $this->updateSysFileReference($reference, $sys_language_uid);
            }
        }
    }

}
