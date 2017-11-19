<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
  * Resources related helper methods
  */
class ResourcesService {

    /**
     * make sure upload storage and folder are in place
     */
    public function prepareFileStorageEnvironment(): FolderInterface
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants']);
        $storageUid = (int)$extensionConfiguration['variantsStorageUid'];
        /** @var ResourcesService $resourcesService */

        $targetFolder = $extensionConfiguration['variantsFolder'];
        try {
            /** @var \TYPO3\CMS\Core\Resource\ResourceStorageInterface storage */
            $storage = $this->retrieveStorageObject($storageUid);

            if (!$storage->hasFolder($targetFolder)) {
                $folder = $storage->createFolder($targetFolder);
            } else {
                $folder = $storage->getFolder($targetFolder);
            }
        } catch (\InvalidArgumentException $exception) {
            throw new \RuntimeException('storage with uid ' . $storageUid . ' is not available. Create it and check the given uid in extension configuration.',
                1490480372);
        }
        return $folder;
    }

    /**
     * @param int $uid
     * @return ResourceStorage
     */
    protected function retrieveStorageObject(int $uid): ResourceStorage
    {
        if ($uid === 0) {
            $storage = ResourceFactory::getInstance()->getDefaultStorage();
            if ($storage === null) {
                throw new \UnexpectedValueException('No default storage found. Declare a storage as default or adapt the extension configuration.', 1490480362);
            }
        } else {
            try {
                $storage = ResourceFactory::getInstance()->getStorageObject($uid);
            }
            catch (\Exception $e) {
                throw new \InvalidArgumentException('Storage with uid ' . $uid . ' is not available. Create it and/or adapt the extension configuration.', 1490480372);
            }
        }

        return $storage;
    }

    /**
     * @param int $fileUid
     * @param int $width
     * @param int $height
     * @param $css_class
     * @return string generatedHtml
     */
    public function generatePreviewImageHtml(int $fileUid, $css_class = 't3-tceforms-sysfile-imagepreview', int $width = 150, int $height = 150)
    {
        $file = ResourceFactory::getInstance()->getFileObject($fileUid);
        $processedFile = $file->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => $width, 'height' => $height]);
        $previewImage = $processedFile->getPublicUrl(true);
        $content = '';
        if ($file->isMissing()) {
            $content .= '<span class="label label-danger label-space-right">'
                . htmlspecialchars(LocalizationUtility::translate('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.file_missing', 'lang'))
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
     * @param array $metaDataRecord
     * @param FolderInterface $folder
     */
    public function copyOriginalFileAndUpdateAllConsumingReferencesToUseTheCopy(
        $sys_language_uid,
        array $metaDataRecord,
        FolderInterface $folder
    ) {
        $fileUid = (int)$metaDataRecord['file'];
        $parentFile = ResourceFactory::getInstance()->getFileObject($fileUid);

        $copy = $parentFile->copyTo($folder);
        $translatedFileUid = $copy->getUid();

        // set translation parameters for the copied file (it serves as translation variant of the original file)
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->update('sys_file')
            ->set('sys_language_uid', (int)$sys_language_uid)
            ->set('l10n_parent', $fileUid)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($translatedFileUid, \PDO::PARAM_INT)
                )
            )->execute();

        // update the translated metadata file to use the translation variant of the original file
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->update('sys_file_metadata')->set('file', $translatedFileUid)->where(
            $queryBuilder->expr()->eq('uid',
                $queryBuilder->createNamedParameter((int)$metaDataRecord['uid'], \PDO::PARAM_INT))
        )->execute();

        // find the references that must use the translation variant now
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $references = $queryBuilder->select('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter((int)$sys_language_uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT))
            )->execute();
        $filteredReferences = [];
        while ($reference = $references->fetch()) {
            $uid = $reference['uid'];
            if ($this->isValidReference($uid)) {
                $filteredReferences[] = $uid;
            }
        }
        // run the update on the found references
        foreach ($filteredReferences as $reference) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->update('sys_file_reference')
                ->set('uid_local', $translatedFileUid)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($reference, \PDO::PARAM_INT)))
                ->execute();
        }
    }

    /**
     * Filters away irrelevant tables and checks for free mode in tt_content records
     * everything else is a valid reference in context of file variants update
     *
     * @param int $uid
     * @return bool
     */
    protected function isValidReference(int $uid): bool
    {
        $isValid = true;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->select('tablenames', 'uid_foreign')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
        );
        $sysFileReferenceRecord = $queryBuilder->execute()->fetch();
        $irrelevantTableNames = ['pages', 'pages_language_overlay', 'sys_file_metadata', 'sys_file'];
        if (in_array($sysFileReferenceRecord['tablenames'], $irrelevantTableNames)) {
            $isValid = false;
        }
        return $isValid;
    }
}
