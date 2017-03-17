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

/**
 * Manipulation of records from tables sys_file and sys_file_metadata
 * used from DataHandlerHook
 *
 * Class FileRecordService
 * @package T3G\AgencyPack\FileVariants\Service
 */
class FileRecordService
{

    /**
     * @var PersistenceService
     */
    protected $persistenceService;

    /**
     * @var ReferenceRecordService
     */
    protected $referenceRecordService;

    /**
     * FileRecordService constructor.
     * @param PersistenceService $persistenceService
     * @param ReferenceRecordService $referenceRecordService
     */
    public function __construct($persistenceService, $referenceRecordService)
    {
        $this->persistenceService = $persistenceService;
        $this->referenceRecordService = $referenceRecordService;
    }

    /**
     * @param int $parentUid
     * @param int $sys_language_uid
     * @return int
     */
    public function translateSysFileRecord(int $parentUid, int $sys_language_uid): int
    {
        if ($sys_language_uid < 1) {
            throw new \InvalidArgumentException('can not translate to default language', 1489334111);
        }
        if ($parentUid < 1) {
            throw new \InvalidArgumentException('can not translate from invalid file', 1489334112);
        }
        $parentFile = $this->persistenceService->getFileObject($parentUid);
        $folder = $this->persistenceService->findStorageDestination();
        $translatedFileUid = $this->persistenceService->copyFileObject($parentFile, $folder)->getUid();

        $dataMap = [
            'sys_file' => [
                $translatedFileUid => [
                    'sys_language_uid' => $sys_language_uid,
                    'l10n_parent' => $parentUid
                ]
            ]
        ];
        $this->persistenceService->process_dataMap($dataMap);

        return $translatedFileUid;
    }

    /**
     * @param $uid
     * @param $fileUid
     */
    public function updateSysFileMetadata(int $uid, int $fileUid)
    {
        if ($uid < 1) {
            throw new \InvalidArgumentException('can not update invalid record', 1489334113);
        }
        if ($fileUid < 1) {
            throw new \InvalidArgumentException('can not relate to invalid file', 1489334114);
        }
        $dataMap = [
            'sys_file_metadata' => [
                $uid => [
                    'file' => $fileUid
                ]
            ]
        ];
        $this->persistenceService->process_dataMap($dataMap);
    }

    /**
     * @param $metadataUid
     * @param $fileName
     */
    public function replaceFileContentOfRelatedFile(int $metadataUid, string $fileName)
    {
        if ($metadataUid < 1) {
            throw new \InvalidArgumentException('no metadata uid given', 1489398159);
        }
        $localFilePath = $this->calculateFullPathToUploadedFile($fileName);
        if (!file_exists($localFilePath)) {
            throw new \RuntimeException('file ' . $fileName . ' was not uploaded', 1489398160);
        }

        $metadataRecord = $this->persistenceService->getSysFileMetaDataRecordByUid($metadataUid);
        $this->persistenceService->replaceFile($metadataRecord['file'], $fileName, $localFilePath);
    }

    /**
     * @param string $fileName
     * @return string filePath
     */
    protected function calculateFullPathToUploadedFile(string $fileName): string
    {
        $sourceFolder = PATH_site . 'typo3temp/uploads/';
        if (strpos($fileName, ',') !== false) {
            $fileName = substr($fileName, strrpos($fileName, ',') + 1);
        }
        return $sourceFolder . $fileName;
    }

    /**
     * @param int $parentFileUid
     * @param int $sys_language_uid
     * @return int
     */
    public function getFileVariantUidForFile(int $parentFileUid, int $sys_language_uid): int
    {
        if ($parentFileUid < 1) {
            throw new \InvalidArgumentException('file uid not valid', 1489503021);
        }
        $result = $this->persistenceService->getSysFileRecord($parentFileUid, $sys_language_uid);
        return isset($result['uid']) ? (int)$result['uid'] : 0;
    }

    /**
     * @param string $table
     * @param $id
     * @param $value
     */
    public function adjustTranslatedReferencesToFileVariants(string $table, $id, $value)
    {
        $handledRecord = $this->persistenceService->getTranslatedRecord($table, (int)$id, (int)$value);
        if (isset($handledRecord['uid']) && (int)$handledRecord['uid'] > 0) {
            $references = $this->referenceRecordService->findReferencesByUidForeignAndSysLanguageUid((int)$handledRecord['uid'],
                (int)$value, $table);
            if ($references) {
                foreach ($references as $reference) {
                    $languageVariantUid = $this->getFileVariantUidForFile((int)$reference['uid_local'],
                        (int)$value);
                    if ($languageVariantUid > 0) {
                        $this->persistenceService->updateReferences([(int)$reference['uid']], (int)$languageVariantUid);
                    }
                }
            }
        }
    }
}
