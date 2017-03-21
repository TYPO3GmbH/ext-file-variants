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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
  * Description
  */
class FileInfoElement extends \TYPO3\CMS\Backend\Form\Element\FileInfoElement{

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

                // find out whether there is an variant present
                $fileVariantExists = $this->areRelatedFilesEqual();
                if ($fileVariantExists === false) {

                    $resultArray['requireJsModules'][] = [
                        'TYPO3/CMS/FileVariants/FileVariants' => 'function(FileVariants){FileVariants.initialize()}'
                    ];

                    /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                    $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                    $path = $uriBuilder->buildUriFromRoute('ajax_tx_filevariants_deleteFileVariant',
                        ['uid' => $this->data['vanillaUid']]);
                    $resultArray['html'] .= '<p><button class="btn btn-default t3js-delete-filevariant-trigger" data-url="' . $path . '">remove language variant</button></p>';
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->select('file')->from('sys_file_metadata')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($l10n_parent, \PDO::PARAM_INT))
        );
        $defaultFileUid = (int)$queryBuilder->execute()->fetchColumn();

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
