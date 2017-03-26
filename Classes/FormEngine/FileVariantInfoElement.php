<?php
declare(strict_types=1);

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
use TYPO3\CMS\Backend\Form\Element\FileInfoElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description
 */
class FileVariantInfoElement extends FileInfoElement
{
    /**
     * @var ResourceStorageInterface
     */
    protected $storage;

    /**
     * @var FolderInterface
     */
    protected $folder;

    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = parent::render();
        if ($this->data['databaseRow']['sys_language_uid'][0] > '0') {

            $fileUid = (int)$this->data['databaseRow']['file'][0];
            if ($fileUid < 1) {
                $resultArray['html'] = 'something went wrong, no valid file uid received (' . $fileUid . ')';
            } else {
                GeneralUtility::makeInstance(PageRenderer::class)->addInlineLanguageLabelFile('EXT:file_variants/Resources/Private/Language/locallang.xlf');
                $resultArray['requireJsModules'][] = 'TYPO3/CMS/FileVariants/FileVariantsDragUploader';
                $resultArray['requireJsModules'][] = [
                    'TYPO3/CMS/FileVariants/FileVariants' => 'function(FileVariants){FileVariants.initialize()}'
                ];

                $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants']);
                $storageUid = (int)$extensionConfiguration['variantsStorageUid'];
                $targetFolder = $extensionConfiguration['variantsFolder'];
                try {
                    $this->storage = ResourceFactory::getInstance()->getStorageObject($storageUid);

                    if (!$this->storage->hasFolder($targetFolder)) {
                        $this->folder = $this->storage->createFolder($targetFolder);
                    } else {
                        $this->folder = $this->storage->getFolder($targetFolder);
                    }
                } catch (\InvalidArgumentException $exception) {
                    throw new \RuntimeException('storage with uid ' . $storageUid . ' is now available. Create it and check the given uid in extension configuration.', 1490480372);
                }

                /** @var UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

                // find out whether there is an variant present
                $fileVariantExists = $this->areRelatedFilesEqual();
                if ($fileVariantExists === false) {
                    // Get sys_file uid by metadata uid record
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('sys_file_metadata')
                    ;
                    $queryBuilder->select('file')->from('sys_file_metadata')->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($this->data['vanillaUid'], \PDO::PARAM_INT)
                        )
                    );
                    $fileUid = (int)$queryBuilder->execute()->fetch()['file'];

                    // Determine language
                    $languageUid = (int)(is_array($this->data['databaseRow']['sys_language_uid'])
                        ? $this->data['databaseRow']['sys_language_uid'][0]
                        : $this->data['databaseRow']['sys_language_uid']
                    );
                    $languageLabel = $this->data['systemLanguageRows'][$languageUid]['title'];

                    // reset variant to default
                    $path = $uriBuilder->buildUriFromRoute('ajax_tx_filevariants_deleteFileVariant',
                        ['uid' => $this->data['vanillaUid']]);
                    $resultArray['html'] .= '<p><button class="btn btn-default t3js-filevariant-trigger" data-url="' . $path . '">remove language variant</button></p>';

                    // upload new file to replace current variant
                    $path = $uriBuilder->buildUriFromRoute('ajax_tx_filevariants_replaceFileVariant', [
                        'uid' => $this->data['vanillaUid'],
                        'sys_file' => $fileUid
                    ]);
                    $resultArray['html'] .= '<p><button class="btn btn-default t3js-filevariant-trigger" data-url="' . $path . '">replace language variant</button></p>';
                } else {
                    // provide upload possibility
                    $maxFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
                    $path = $uriBuilder->buildUriFromRoute('ajax_tx_filevariants_uploadFileVariant',
                        ['uid' => $this->data['vanillaUid']]);
                    $resultArray['html'] .= '<div class="t3js-filevariants-drag-uploader" data-target-folder="' .$this->folder->getCombinedIdentifier(). '" data-progress-container="#typo3-filelist"
	 data-dropzone-trigger=".t3js-drag-uploader-trigger" data-dropzone-target=".t3js-module-body h1:first"
	 data-file-deny-pattern="' .$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']. '" data-max-file-size="' .$maxFileSize. '" data-handling-url="' .$path. '"
	></div>';
                    //$resultArray['html'] .= '<p><button class="btn btn-default t3js-filevariant-trigger t3js-drag-uploader" data-url="' . $path . '">create language variant</button></p>';
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
        $l10n_parent = (int)$this->data['databaseRow']['l10n_parent'][0];
        $fileUid = (int)$this->data['databaseRow']['file'][0];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->select('file')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($l10n_parent, \PDO::PARAM_INT))
        );
        $defaultFileUid = (int)$queryBuilder->execute()->fetchColumn();
        // this file has not been copied upon metadata translation. Probably we talk stale data.
        // make sure there will be no error at least.
        if ($defaultFileUid === $fileUid) {
            return true;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->select('sha1')->from('sys_file')->where(
            $queryBuilder->expr()->in('uid',
                $queryBuilder->createNamedParameter([$fileUid, $defaultFileUid], Connection::PARAM_INT_ARRAY))
        );
        $sha1s = $queryBuilder->execute()->fetchAll();
        return $sha1s[0]['sha1'] === $sha1s[1]['sha1'];
    }
}