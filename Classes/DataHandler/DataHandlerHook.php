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
     * @param FileRecordService $fileRecordService
     */
    public function initializeServices($fileRecordService = null)
    {
        $this->fileRecordService = $fileRecordService;
        if ($this->fileRecordService === null) {
            $this->fileRecordService = GeneralUtility::makeInstance(FileRecordService::class);
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
    ) {

        $this->initializeServices();

        // sys_file_metadata record is updated with file_variants set
        if ($table === 'sys_file_metadata' && $status === 'update' && array_key_exists('file_variant', $fieldArray)) {
            $this->fileRecordService->relateFileVariantToSysFileMetadataRecord();
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
    }

}
