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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bundles all requests to the database
 *
 * helper for unit tests
 *
 * Class PersistenceService
 * @package T3G\AgencyPack\FileVariants\DataHandler
 */
class PersistenceService
{

    /**
     * @param int $l10n_parent
     * @param int $sys_language_uid
     * @return array
     */
    public function getSysFileMetaDataRecord(int $l10n_parent, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->select('*')->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($l10n_parent, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)
                )
            );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param int $parentUid
     * @param int $sys_language_uid
     * @return array
     */
    public function getSysFileRecord(int $parentUid, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->select('*')->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($parentUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)
                )
            );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param $cmdMap
     */
    public function process_cmdMap($cmdMap)
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmdMap);
        $dataHandler->process_cmdmap();
    }

    /**
     * @param $dataMap
     */
    public function process_dataMap($dataMap)
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param int $fileUid
     * @param int $sys_language_uid
     * @return array
     */
    public function collectAffectedReferences(int $fileUid, int $sys_language_uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('uid')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($sys_language_uid, \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
        );
        return $queryBuilder->execute()->fetchAll();

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
     * @param int $uid
     * @return bool
     */
    protected function isValidReference(int $uid): bool
    {
        $isValid = true;
        $sysFileReferenceRecord = $this->getSysFileReferenceRecord($uid);
        $irrelevantTableNames = ['pages', 'pages_language_overlay', 'sys_file_metadata', 'sys_file'];
        if (in_array($sysFileReferenceRecord['tablenames'], $irrelevantTableNames)) {
            return false;
        }
        $foreignRecord = $this->getRecord($sysFileReferenceRecord['tablenames'], $sysFileReferenceRecord['uid_foreign']);
        if ($sysFileReferenceRecord['tablenames'] === 'tt_content' && $foreignRecord['l18n_parent'] === 0) {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * @param array $references
     * @param int $newFileUid
     */
    public function updateReferences(array $references, int $newFileUid)
    {
        $change = ['uid_local' => $newFileUid];
        $dataMap = ['sys_file_reference' => []];
        foreach ($references as $reference) {
            $dataMap['sys_file_reference'][$reference] = $change;
        }
        $this->process_dataMap($dataMap);
    }

    /**
     * @param int $uid
     * @return array
     */
    protected function getSysFileReferenceRecord(int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('*')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );
        return $queryBuilder->execute()->fetch();
    }

    /**
     * @param string $table
     * @param int $uid
     * @return array
     */
    protected function getRecord(string $table, int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->select('*')->from($table)->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );

        return $queryBuilder->execute()->fetch();
    }

}