<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Controller;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileVariantsController
{
    /**
     * @return ResponseInterface
     */
    public function ajaxResetFileVariant(ServerRequestInterface $request): ResponseInterface
    {
        $uid = (int)$request->getQueryParams()['uid'];

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'vanillaUid' => $uid,
            'command' => 'edit',
        ];
        if ((new Typo3Version())->getMajorVersion() < 12) {
            unset($formDataCompilerInput['request']);
        }
        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $formData['renderType'] = 'fileInfo';

        $fileUid = (int)$formData['databaseRow']['file'][0];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $fileRecord = $queryBuilder->select('l10n_parent')->from('sys_file')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT)))->executeQuery()->fetchAssociative();

        $defaultFileObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$fileRecord['l10n_parent']);
        /** @var Folder */
        $parentFolder = $defaultFileObject->getParentFolder();
        $copy = $defaultFileObject->copyTo($parentFolder);
        // this record will be stale after the replace, remove it right away
        $sysFileRecordToBeDeleted = $copy->getUid();
        $path = $this->getAbsolutePathToFile($copy);

        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
        $file->getStorage()->replaceFile($file, $path);
        $file->rename($defaultFileObject->getName(), DuplicationBehavior::RENAME);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->delete('sys_file')->where($queryBuilder->expr()
            ->eq('uid', $queryBuilder->createNamedParameter($sysFileRecordToBeDeleted, Connection::PARAM_INT)))->executeStatement();

        // metadata records for copied file are not needed, either
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_metadata');
        $metadataRecordsToBeDeleted = $queryBuilder->select('uid')->from('sys_file_metadata')->where($queryBuilder->expr()->eq(
            'file',
            $queryBuilder->createNamedParameter($sysFileRecordToBeDeleted, Connection::PARAM_INT)
        ))->executeQuery()->fetchFirstColumn();
        if (count($metadataRecordsToBeDeleted) > 0) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->delete('sys_file_metadata')->where($queryBuilder->expr()
                ->in('uid', $metadataRecordsToBeDeleted))->executeStatement();
        }

        //        /** @var $refIndexObj ReferenceIndex */
        //        $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
        //        $refIndexObj->updateIndex(false);

        $formResult = $nodeFactory->create($formData)->render();
        $response = new HtmlResponse($formResult['html']);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function ajaxReplaceFileVariant(ServerRequestInterface $request): ResponseInterface
    {
        return $this->ajaxUploadFileVariant($request);
    }

    /**
     * @return ResponseInterface
     */
    public function ajaxUploadFileVariant(ServerRequestInterface $request): ResponseInterface
    {
        $uploadedFileUid = (int)$request->getQueryParams()['file'];
        $metadataUid = (int)$request->getQueryParams()['uid'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $currentFileUid = $queryBuilder->select('file')->from('sys_file_metadata')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($metadataUid, Connection::PARAM_INT)))->executeQuery()->fetchOne();

        $currentFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($currentFileUid);
        $uploadedFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($uploadedFileUid);
        $currentFile->getStorage()->replaceFile($currentFile, $this->getAbsolutePathToFile($uploadedFile));
        $currentFile->rename($uploadedFile->getName(), DuplicationBehavior::RENAME);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->delete('sys_file')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uploadedFileUid, Connection::PARAM_INT)))->executeStatement();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->delete('sys_file_metadata')->where($queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($uploadedFileUid, Connection::PARAM_INT)))->executeStatement();

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $formDataCompilerInput = [
            'request' => $request,
            'tableName' => 'sys_file_metadata',
            'vanillaUid' => $metadataUid,
            'command' => 'edit',
        ];
        if ((new Typo3Version())->getMajorVersion() < 12) {
            unset($formDataCompilerInput['request']);
        }
        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $formData['renderType'] = 'fileInfo';

        $formResult = $nodeFactory->create($formData)->render();
        $response = new HtmlResponse($formResult['html']);

        return $response;
    }

    /**
     * Returns an absolute file path to the given File resource
     *
     * @return string
     */
    protected function getAbsolutePathToFile(File $file): string
    {
        $storage = $file->getStorage();
        if (!$storage->isPublic()) {
            // manually create a possibly valid file path from the storage configuration
            $storageConfiguration = $storage->getConfiguration();
            if ($storageConfiguration['pathType'] == 'absolute') {
                $path = realpath($storageConfiguration['basePath']) . $file->getIdentifier();
            } else {
                $path = realpath(Environment::getPublicPath() . '/' . $storageConfiguration['basePath']) . $file->getIdentifier();
            }
        } else {
            $path = Environment::getPublicPath() . '/' . $file->getPublicUrl();
        }

        return $path;
    }
}
