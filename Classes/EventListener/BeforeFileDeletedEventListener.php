<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\EventListener;

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
use TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BeforeFileDeletedEventListener
{
    public function __invoke(BeforeFileDeletedEvent $event): void
    {
        $file = $event->getFile();

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
}
