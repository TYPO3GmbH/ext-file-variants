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
 * Manipulation of records from table sys_file_reference
 * Used from DataHandlerHook
 *
 * Class ReferenceRecordService
 * @package T3G\AgencyPack\FileVariants\Service
 */
class ReferenceRecordService
{
    /**
     * @var PersistenceService
     */
    protected $persistenceService;

    /**
     * @var RecordService
     */
    protected $recordService;

    /**
     * ReferenceRecordService constructor.
     * @param PersistenceService $persistenceService
     * @param RecordService $recordService
     */
    public function __construct($persistenceService, $recordService)
    {
        $this->persistenceService = $persistenceService;
        $this->recordService = $recordService;
    }

    /**
     * @param int $oldFileUid
     * @param int $newFileUid
     * @param int $sys_language_uid
     */
    public function updateReferences(int $oldFileUid, int $newFileUid, int $sys_language_uid)
    {
        if ($oldFileUid < 1) {
            throw new \InvalidArgumentException('can not find invalid file references', 1489335146);
        }
        if ($newFileUid < 1) {
            throw new \InvalidArgumentException('can not replace with invalid file references', 1489335147);
        }
        if ($sys_language_uid < 1) {
            throw new \InvalidArgumentException('can not handle invalid language', 1489335148);
        }
        $references = $this->persistenceService->collectAffectedReferences($oldFileUid, $sys_language_uid);
        $references = $this->recordService->filterValidReferences($references);
        $this->persistenceService->updateReferences($references, $newFileUid);
    }

    /**
     * @param int $uid_foreign
     * @param int $sys_language_uid
     * @param string $tableName
     * @return array
     */
    public function findReferencesByUidForeignAndSysLanguageUid(int $uid_foreign, int $sys_language_uid, string $tableName): array
    {
        if ($uid_foreign < 1) {
            throw new \InvalidArgumentException('value for uid_foreign is no valid id', 1489498930);
        }
        if ($sys_language_uid < 1) {
            throw new \InvalidArgumentException('value for sys_language_uid is no valid language id', 1489498931);
        }
        return $this->persistenceService->findReferencesByUidForeignAndSysLanguageUid($uid_foreign, $sys_language_uid, $tableName);
    }

}
