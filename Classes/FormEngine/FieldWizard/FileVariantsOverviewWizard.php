<?php
declare(strict_types=1);

namespace T3G\AgencyPack\FileVariants\FormEngine\FieldWizard;

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
use T3G\AgencyPack\FileVariants\Service\ResourcesService;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Description
 */
class FileVariantsOverviewWizard extends AbstractNode
{

    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $languages = [];
        $languageRecords = $queryBuilder->select('uid', 'title', 'language_isocode')->from('sys_language')->execute();
        while ($language = $languageRecords->fetch()) {
            $languages[(int)$language['uid']] = $language['title'] . ' (' . $language['language_isocode'] . ')';
        }

        // no parent - we are in default language
        $parentField = (int)$this->data['databaseRow']['l10n_parent'][0];
        if ($parentField === 0) {
            $result['html'] .= '<div class="variants-preview">';
            $resourcesService = GeneralUtility::makeInstance(ResourcesService::class);
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
            $translations = $queryBuilder->select('file', 'sys_language_uid')->from('sys_file_metadata')->where(
                $queryBuilder->expr()->eq('l10n_parent',
                    $queryBuilder->createNamedParameter((int)$this->data['databaseRow']['uid'], \PDO::PARAM_INT))
            )->execute();
            while ($translation = $translations->fetch()) {
                $result['html'] .= '<p class="t3-sysfile-translation">';
                $result['html'] .= '<span>' . $languages[(int)$translation['sys_language_uid']] . '</span>';
                $result['html'] .= $resourcesService->generatePreviewImageHtml((int)$translation['file'], 't3-tceforms-sysfile-translation-imagepreview');
                $result['html'] .= '</p>';
            }
            $result['html'] .= '</div>';
        }

        $result['stylesheetFiles'][] = 'EXT:file_variants/Resources/Public/Css/FileVariantInfoElement.css';

        return $result;
    }
}
