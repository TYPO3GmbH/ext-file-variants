<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
  * Resources related helper methods
  */
class ResourcesService
{

    /**
     * make sure upload storage and folder are in place
     */
    public function prepareFileStorageEnvironment(): FolderInterface
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('file_variants');
        $storageUid = (int)$extensionConfiguration['variantsStorageUid'];
        $targetFolder = $extensionConfiguration['variantsFolder'];
        try {
            $storage = $this->retrieveStorageObject($storageUid);

            if (!$storage->hasFolder($targetFolder)) {
                $folder = $storage->createFolder($targetFolder);
            } else {
                $folder = $storage->getFolder($targetFolder);
            }
        } catch (\InvalidArgumentException) {
            throw new \RuntimeException(
                'storage with uid ' . $storageUid . ' is not available. Create it and check the given uid in extension configuration.',
                1490480372
            );
        }
        return $folder;
    }

    /**
     * @return ResourceStorage
     */
    protected function retrieveStorageObject(int $uid): ResourceStorage
    {
        if ($uid === 0) {
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();
            if ($storage === null) {
                throw new \UnexpectedValueException('No default storage found. Declare a storage as default or adapt the extension configuration.', 1490480362);
            }
        } else {
            try {
                $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($uid);
            } catch (\Exception) {
                throw new \InvalidArgumentException('Storage with uid ' . $uid . ' is not available. Create it and/or adapt the extension configuration.', 1490480372);
            }
        }

        return $storage;
    }

    /**
     * @param $css_class
     * @return string generatedHtml
     */
    public function generatePreviewImageHtml(int $fileUid, $css_class = 't3-tceforms-sysfile-imagepreview', int $width = 150, int $height = 150)
    {
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
        $processedFile = $file->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => $width, 'height' => $height]);
        $previewImage = $processedFile->getPublicUrl();
        $content = '';
        if ($file->isMissing()) {
            $content .= '<span class="label label-danger label-space-right">'
                . htmlspecialchars((string) LocalizationUtility::translate(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing',
                    'core'
                ))
                . '</span>';
        }
        if ($previewImage) {
            $content .= '<img src="' . htmlspecialchars($previewImage) . '" ' .
                'width="' . $processedFile->getProperty('width') . '" ' .
                'height="' . $processedFile->getProperty('height') . '" ' .
                'alt="" class="' . $css_class . '" />';
        }
        return $content;
    }

    /**
     * @param int $sys_language_uid
     */
    public function copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy(
        $sys_language_uid,
        array $metaDataRecord,
        FolderInterface $folder
    ) {
        $fileUid = (int)$metaDataRecord['file'];
        $parentFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);

        $copy = $parentFile->copyTo($folder);
        $translatedFileUid = $copy->getUid();

        // set translation parameters for the copied file (it serves as translation variant of the original file)
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->update('sys_file')
            ->set('sys_language_uid', (int)$sys_language_uid)
            ->set('l10n_parent', $fileUid)->where($queryBuilder->expr()->eq(
            'uid',
            $queryBuilder->createNamedParameter($translatedFileUid, \PDO::PARAM_INT)
        ))->executeStatement();

        // update the translated metadata file to use the translation variant of the original file
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->update('sys_file_metadata')->set('file', $translatedFileUid)->where($queryBuilder->expr()->eq(
            'uid',
            $queryBuilder->createNamedParameter((int)$metaDataRecord['uid'], \PDO::PARAM_INT)
        ))->executeStatement();

        // find the references that must use the translation variant now
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $references = $queryBuilder->select('uid')
            ->from('sys_file_reference')->where($queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter((int)$sys_language_uid, \PDO::PARAM_INT)), $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)))->executeQuery();
        $filteredReferences = [];
        while ($reference = $references->fetchAssociative()) {
            $uid = $reference['uid'];
            if ($this->isValidReference($uid)) {
                $filteredReferences[] = $uid;
            }
        }
        // run the update on the found references
        foreach ($filteredReferences as $reference) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->update('sys_file_reference')
                ->set('uid_local', $translatedFileUid)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($reference, \PDO::PARAM_INT)))->executeStatement();
        }
    }

    /**
     * Filters away irrelevant tables and checks for free mode in tt_content records
     * everything else is a valid reference in context of file variants update
     *
     * @return bool
     */
    protected function isValidReference(int $uid): bool
    {
        $isValid = true;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $sysFileReferenceRecord = $queryBuilder->select('tablenames', 'uid_foreign')->from('sys_file_reference')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))->executeQuery()->fetchAssociative();
        $irrelevantTableNames = ['pages', 'sys_file_metadata', 'sys_file'];
        if (in_array($sysFileReferenceRecord['tablenames'], $irrelevantTableNames)) {
            $isValid = false;
        }
        return $isValid;
    }
}
