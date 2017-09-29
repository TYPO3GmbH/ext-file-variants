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
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
  * Resources related helper methods
  */
class ResourcesService {

    /**
     * @param int $uid
     * @return ResourceStorage
     */
    public function retrieveStorageObject(int $uid): ResourceStorage
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

}
