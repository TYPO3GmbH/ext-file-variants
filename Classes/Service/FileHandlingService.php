<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
  * Description
  */
class FileHandlingService {

    /**
     * @var PersistenceService
     */
    protected $persistenceService;

    /**
     * FileHandlingService constructor.
     * @param PersistenceService $persistenceService
     */
    public function __construct($persistenceService = null)
    {
        $this->persistenceService = $persistenceService;
        if ($this->persistenceService === null) {
            $this->persistenceService = GeneralUtility::makeInstance(PersistenceService::class);
        }
    }


    /**
     * @param string $fileName
     * @return File
     */
    public function moveUploadedFileAndCreateFileObject(string $fileName): File
    {
        $file = null;
        $filePath = $this->calculateFullPathToUploadedFile($fileName);
        if (file_exists($filePath) && is_file($filePath)) {
            $folder = $this->findStorageDestination();
            $file = $folder->addFile($filePath, '', DuplicationBehavior::RENAME);
        }
        if ($file === null) {
            throw new \RuntimeException ('Could not create file object', 1488297422);
        }
        return $file;
    }

    /**
     * @param string $fileName
     * @return string filePath
     */
    public function calculateFullPathToUploadedFile(string $fileName): string
    {
        $sourceFolder = PATH_site . 'typo3temp/uploads/';
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
        $fileStorageRecordUid = $this->persistenceService->getFileStorageRecordUid('language_variants');

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
            $folder = $storage->createFolder($folderName, $storage->getFolder('/'));
        } else {
            $folder = $storage->getFolder($folderName);
        }
        return $folder;
    }



}
