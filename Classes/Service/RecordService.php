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
 
 /**
  * Record related operations
  */
class RecordService {

    /**
     * @var PersistenceService
     */
    protected $persistenceService;

    /**
     * RecordService constructor.
     */
    public function __construct($persistenceService)
    {
        $this->persistenceService = $persistenceService;
    }


    /**
     * @param $references
     * @return array
     */
    public function filterValidReferences($references): array
    {
        $filteredReferences = [];
        foreach ($references as $reference) {
            $uid = $reference['uid'];
            if ($this->isValidReference($uid)) {
                $filteredReferences[] = $uid;
            }
        }
        return $filteredReferences;
    }

    /**
     * Filters away irrelevant tables and checks for free mode in tt_content records
     * everything else is a valid reference in context of file variants update
     *
     * @param int $uid
     * @return bool
     */
    protected function isValidReference(int $uid): bool
    {
        $isValid = true;
        $sysFileReferenceRecord = $this->persistenceService->getSysFileReferenceRecord($uid);
        $irrelevantTableNames = ['pages', 'pages_language_overlay', 'sys_file_metadata', 'sys_file'];
        if (in_array($sysFileReferenceRecord['tablenames'], $irrelevantTableNames)) {
            return false;
        }
        $foreignRecord = $this->persistenceService->getRecord($sysFileReferenceRecord['tablenames'], $sysFileReferenceRecord['uid_foreign']);
        if ($sysFileReferenceRecord['tablenames'] === 'tt_content' && $foreignRecord['l18n_parent'] === 0) {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * @param string $table
     * @return bool
     */
    public function isFalConsumingTable(string $table): bool
    {
        $irrelevantTables = [
            'sys_file',
            'sys_file_metadata',
            'sys_log',
            'sys_history',
            'sys_log',
            'sys_workspace',
            'tx_extensionmanager_domain_model_extension',
            'tx_extensionmanager_domain_model_repository',
        ];
        if (in_array($table, $irrelevantTables)) {
            return false;
        }
        foreach ($GLOBALS['TCA'][$table]['columns'] as $column) {
            if ($column['config']['foreign_table'] === 'sys_file_reference') {
                return true;
            }
        }
        return false;
    }
}
