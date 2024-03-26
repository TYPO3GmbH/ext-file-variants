<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\FormEngine;

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
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Backend\Form\Element\FileInfoElement;
use T3G\AgencyPack\FileVariants\Service\ResourcesService;

/**
 * Description
 */
class FileVariantInfoElement extends FileInfoElement
{

    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = parent::render();
        $languageUid = $this->data['databaseRow']['sys_language_uid'];

        if ($languageUid > 0) {
            $fileUid = (int)$this->data['databaseRow']['file'][0];
            if ($fileUid < 1) {
                $resultArray['html'] = 'something went wrong, no valid file uid received (' . $fileUid . ')';
            } else {
                GeneralUtility::makeInstance(PageRenderer::class)->addInlineLanguageLabelFile('EXT:file_variants/Resources/Private/Language/locallang.xlf');
                $resultArray['requireJsModules'][] = [
                    'TYPO3/CMS/FileVariants/FileVariantsDragUploader' => 'function(fileVariantsDragUploader){fileVariantsDragUploader.initialize()}'
                ];
                $resultArray['requireJsModules'][] = [
                    'TYPO3/CMS/FileVariants/FileVariants' => 'function(FileVariants){FileVariants.initialize()}'
                ];
                $resultArray['stylesheetFiles'][] = 'EXT:file_variants/Resources/Public/Css/FileVariantInfoElement.css';

                /** @var ResourcesService $resourcesService */
                $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
                /** @var Folder */
                $folder = $resourcesService->prepareFileStorageEnvironment();

                /** @var UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

                // find out whether there is an variant present
                $fileVariantExists = $this->areRelatedFilesEqual();
                if ($fileVariantExists === false) {
                    // reset variant to default
                    $path = $uriBuilder->buildUriFromRoute('ajax_tx_filevariants_deleteFileVariant', ['uid' => $this->data['vanillaUid']]);
                    $resultArray['html'] .= '<p><button class="btn btn-default t3js-filevariant-trigger" data-url="' . $path . '">remove language variant</button></p>';
                    $defaultFileUid = $this->getDefaultFileUid();

                    $resultArray['html'] = '<div class="t3-sysfile-metadata">' . $resultArray['html'] . '</div>';

                    $resultArray['html'] .= '<div class="t3-sysfile-default">';
                    $resultArray['html'] .= '<span>Default file:</span>';
                    $resultArray['html'] .= $resourcesService->generatePreviewImageHtml($defaultFileUid, 't3-tceforms-sysfile-default-imagepreview');
                    $resultArray['html'] .= '</div>';

                    $resultArray['html'] = '<div class="t3-sysfile-wrapper">' . $resultArray['html'] . '</div>';

                    // upload new file to replace current variant
                    $maxFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
                    $path = $uriBuilder->buildUriFromRoute(
                        'ajax_tx_filevariants_replaceFileVariant',
                        ['uid' => $this->data['vanillaUid']]
                    );
                    $resultArray['html'] .= '<div class="t3js-filevariants-drag-uploader" data-target-folder="' . $folder->getCombinedIdentifier() . '"
     data-dropzone-trigger=".dropzone" data-dropzone-target=".t3js-module-body h1:first"
     data-file-deny-pattern="' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . '" data-max-file-size="' . $maxFileSize . '" data-handling-url="' . $path . '"
    ></div>';
                } else {
                    $resultArray['html'] = '<div class="t3-sysfile-wrapper">' . $resultArray['html'] . '</div>';

                    // provide upload possibility
                    $maxFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
                    $path = $uriBuilder->buildUriFromRoute(
                        'ajax_tx_filevariants_uploadFileVariant',
                        ['uid' => $this->data['vanillaUid']]
                    );
                    $resultArray['html'] .= '<div class="t3js-filevariants-drag-uploader" data-target-folder="' . $folder->getCombinedIdentifier() . '"
     data-dropzone-trigger=".dropzone" data-dropzone-target=".t3js-module-body h1:first"
     data-file-deny-pattern="' . $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . '" data-max-file-size="' . $maxFileSize . '" data-handling-url="' . $path . '"
    ></div>';
                }
            }
        }

        $resultArray['html'] = '<div id=t3js-fileinfo>' . $resultArray['html'] . '</div>';

        return $resultArray;
    }

    /**
     * @return bool
     */
    protected function areRelatedFilesEqual(): bool
    {
        $fileUid = (int)$this->data['databaseRow']['file'][0];
        $defaultFileUid = $this->getDefaultFileUid();

        // this file has not been copied upon metadata translation. Probably we talk stale data.
        // make sure there will be no error at least.
        if ($defaultFileUid === $fileUid) {
            return true;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $sha1s = $queryBuilder->select('sha1')->from('sys_file')->where($queryBuilder->expr()->in(
            'uid',
            $queryBuilder->createNamedParameter([$fileUid, $defaultFileUid], Connection::PARAM_INT_ARRAY)
        ))->executeQuery()->fetchAllAssociative();
        return $sha1s[0]['sha1'] === $sha1s[1]['sha1'];
    }

    /**
     * @return int
     */
    protected function getDefaultFileUid(): int
    {
        $l10nParent = $this->data['databaseRow']['l10n_parent'][0]['uid'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        return (int)$queryBuilder->select('file')->from('sys_file_metadata')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($l10nParent, Connection::PARAM_INT)))->executeQuery()->fetchOne();
    }
}
