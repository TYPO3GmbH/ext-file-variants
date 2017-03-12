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
     * FileRecordService constructor.
     * @param PersistenceService $persistenceService
     */
    public function __construct($persistenceService)
    {
        $this->persistenceService = $persistenceService;
    }

    /**
     * @param int $parentUid
     * @param int $sys_language_uid
     * @return int
     */
    public function copySysFileRecord(int $parentUid, int $sys_language_uid): int
    {
        if ($sys_language_uid < 1) {
            throw new \InvalidArgumentException('can not translate to default language', 1489334111);
        }
        if ($parentUid < 1) {
            throw new \InvalidArgumentException('can not translate from invalid file', 1489334112);
        }
        $cmdMap = [
            'sys_file' => [
                $parentUid => [
                    'localize' => $sys_language_uid
                ]
            ]
        ];
        $this->persistenceService->process_cmdMap($cmdMap);


        $sysFileRecord = $this->persistenceService->getSysFileRecord($parentUid, $sys_language_uid);
        return $sysFileRecord['uid'];
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

    public function updateSysFileRecord()
    {
    }


}