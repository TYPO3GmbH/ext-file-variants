<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
use TYPO3\CMS\Core\Database\Connection;
use T3G\AgencyPack\FileVariants\Service\ResourcesService;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHook
{
    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        try {
            GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('file_variants');
        } catch (ExtensionConfigurationExtensionNotConfiguredException $e) {
            throw new \RuntimeException(
                'No extension configuration found. Go to ExtensionManager and press the wheel symbol for ext:file_variants.',
                1490476773,
                $e
            );
        }
        $uploadFolderPath = Environment::getPublicPath() . '/typo3temp/file_variants_uploads';
        if (!is_dir($uploadFolderPath)) {
            mkdir($uploadFolderPath, 2777, true);
        }
    }

    /**
     *
     * replaces the uid of the default language sys_file record uid with the translated one
     *
     * @param $id
     */
    public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array &$fieldArray)
    {
        if ($table === 'sys_file_reference' && isset($fieldArray['sys_language_uid']) && (int)$fieldArray['sys_language_uid'] > 0) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $parentFile = (int)$queryBuilder
                ->select('uid_local')
                ->from('sys_file_reference')->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter((int)$fieldArray['l10n_parent'], Connection::PARAM_INT)
            ))->executeQuery()
                ->fetchOne();
            $fileVariantUid = $this->findLanguageVariantForLanguageAndParentFile((int)$fieldArray['sys_language_uid'], $parentFile);
            if ($fileVariantUid > 0) {
                $fieldArray['uid_local'] = $fileVariantUid;
            }
        }
    }

    /**
     * @param string|int $id recordUid
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        mixed $value,
        DataHandler $pObj
    ) {

        // translation of any sys_file_reference consuming record.
        if ($command === 'localize' || $command === 'copyToLanguage') {
            $id = $this->substNewWithId($id, $pObj);
            if ($id < 1) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332067);
            }
            $handledRecords = $pObj->copyMappingArray;

            if (array_key_exists(
                'sys_file_reference',
                $handledRecords
            ) && is_array($handledRecords['sys_file_reference'])
            ) {
                $references = $handledRecords['sys_file_reference'];

                foreach ($references as $reference) {
                    /** @var QueryBuilder $queryBuilder */
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $currentFileId = (int)$queryBuilder->select('uid_local')->from('sys_file_reference')->where($queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$reference, Connection::PARAM_INT)
                    ))->executeQuery()->fetchOne();
                    $fileVariantUid = $this->findLanguageVariantForLanguageAndParentFile((int)$value, $currentFileId);

                    if ((int)$fileVariantUid > 0) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                        $queryBuilder->update('sys_file_reference')->set('uid_local', $fileVariantUid)->where($queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter((int)$reference, Connection::PARAM_INT)
                        ))->executeStatement();
                    }
                }
            }
        }

        // translation of metadata record
        // results in copied sys_file and relation of record to new file
        // all references need to be updated to the new file
        if ($table === 'sys_file_metadata' && $command === 'localize') {
            $id = $this->substNewWithId($id, $pObj);
            if ($id < 1) {
                throw new \RuntimeException('can\'t retrieve valid id', 1489332067);
            }

            // do this here in order to fail early, if no valid setup can be determined
            $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
            $folder = $resourcesService->prepareFileStorageEnvironment();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $handledMetaDataRecord = $queryBuilder->select('uid', 'file')->from('sys_file_metadata')->where($queryBuilder->expr()->eq(
                'l10n_parent',
                $queryBuilder->createNamedParameter((int)$id, Connection::PARAM_INT)
            ), $queryBuilder->expr()->eq(
                'sys_language_uid',
                $queryBuilder->createNamedParameter((int)$value, Connection::PARAM_INT)
            ))->executeQuery()->fetchAssociative();
            $resourcesService->copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy($value, $handledMetaDataRecord, $folder);
        }
    }

    /**
     * @param string|int $id
     * @return int
     */
    protected function substNewWithId($id, DataHandler $pObj): int
    {
        if (is_string($id) && str_contains($id, 'NEW')) {
            $id = $pObj->substNEWwithIDs[$id] ?? null;
        }
        if ($id === null) {
            $id = -1;
        }
        return (int)$id;
    }

    /**
     * @return int
     */
    protected function findLanguageVariantForLanguageAndParentFile(int $sys_language_uid, int $currentFileId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $fileRecord = $queryBuilder->select('sys_language_uid', 'l10n_parent')->from('sys_file')->where($queryBuilder->expr()->eq(
            'uid',
            $queryBuilder->createNamedParameter($currentFileId, Connection::PARAM_INT)
        ))->executeQuery()->fetchAssociative();
        if ((int)$fileRecord['sys_language_uid'] === 0) {
            $queryBuilder->select('uid')->from('sys_file')->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($currentFileId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, Connection::PARAM_INT)
                )
            );
        } else {
            $queryBuilder->select('uid')->from('sys_file')->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($fileRecord['l10n_parent'], Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, Connection::PARAM_INT)
                )
            );
        }

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }
}
