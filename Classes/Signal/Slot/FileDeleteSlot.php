<?php
declare(strict_types=1);

namespace T3G\AgencyPack\FileVariants\Signal\Slot;

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileDeleteSlot
{

    /**
     * @param FileInterface $file
     */
    public function handleFileVariantDeletionPreDelete(FileInterface $file)
    {
        if ($file instanceof File) {
            $fileUid = $file->getUid();
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $parentFileUid = (int)$queryBuilder->select('l10n_parent')->from('sys_file')->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )->execute()->fetchColumn();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $references = $queryBuilder->select('uid')->from('sys_file_reference')->where(
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )->execute();
            foreach ($references->fetchAll(\PDO::FETCH_COLUMN) as $referenceUid) {
                /** @var QueryBuilder $queryBuilder */
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
                $queryBuilder->update('sys_file_reference')->set('uid_local', $parentFileUid)->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($referenceUid, \PDO::PARAM_INT))
                )->execute();
            }
        }
    }

    /**
     * @param FileInterface $file
     */
    public function handleFileVariantDeletionPostDelete(FileInterface $file)
    {
        if ($file instanceof File) {
            // delete file metadata
            $fileUid = $file->getUid();
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->delete('sys_file_metadata')->where(
                $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )->execute();

            // delete all file variants
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $fileVariants = $queryBuilder->select('uid')->from('sys_file')->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )->execute();
            foreach ($fileVariants->fetchAll(\PDO::FETCH_COLUMN) as $variantUid) {
                /** @var File $variantFile */
                $variantFile = ResourceFactory::getInstance()->getFileObject($variantUid);
                $variantFile->getStorage()->deleteFile($variantFile);
            }
        }
    }
}
