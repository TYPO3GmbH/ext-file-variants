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
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class AfterFileDeletedEventListener
{
    public function __invoke(AfterFileDeletedEvent $event): void
    {
        $file = $event->getFile();

        if ($file instanceof File) {
            // delete file metadata
            $fileUid = $file->getUid();
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->delete('sys_file_metadata')->where($queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)))->executeStatement();

            // delete all file variants
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $fileVariants = $queryBuilder->select('uid')->from('sys_file')->where($queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)))->executeQuery();
            foreach ($fileVariants->fetchFirstColumn() as $variantUid) {
                /** @var File $variantFile */
                $variantFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($variantUid);
                $variantFile->getStorage()->deleteFile($variantFile);
            }
        }
    }
}
