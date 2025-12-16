<?php

declare(strict_types=1);

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

namespace T3G\AgencyPack\FileVariants\LinkBuilder;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/**
 * Builds a TypoLink to a file using languageVariants (if available)
 *
 * Was copied from core FileOrFolderLinkBuilder but modified to
 * - use languageVariants.
 * - consider support for v11 and v12 with minor differences in FileOrFolderLinkBuilder
 *
 * Should be used with $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['file'] = FileLinkBuilder::class;
 * (see ext_localconf.php)
 */
class FileLinkBuilder extends AbstractTypolinkBuilder
{
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        $fileOrFolderObject = ($linkDetails['file'] ?? false) ?: ($linkDetails['folder'] ?? null);
        // check if the file exists or if a / is contained (same check as in detectLinkType)
        if (!($fileOrFolderObject instanceof FileInterface) && !($fileOrFolderObject instanceof Folder)) {
            throw new UnableToLinkException(
                'File "' . $linkDetails['typoLinkParameter'] . '" did not exist, so "' . $linkText . '" was not linked.',
                1490989449,
                null,
                $linkText
            );
        }

        // is file, check if languageVariant exists
        if ($fileOrFolderObject instanceof FileInterface && $this->typoScriptFrontendController->getLanguage()->getLanguageId() > 0) {
            $languageId = $this->typoScriptFrontendController->getLanguage()->getLanguageId();
            $properties = $fileOrFolderObject->getProperties();
            $fileUid = (int)($properties['uid'] ?? 0);
            $metadata = $fileOrFolderObject->getMetaData();
            if ($metadata && isset($metadata['sys_language_uid']) && $metadata['sys_language_uid'] === $languageId) {
                $languageVariantFileUid = (int)($metadata['file'] ?? 0);
                if ($languageVariantFileUid > 0 && $fileUid != $languageVariantFileUid) {
                    // There is a language variant file
                    $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                    $languageVariantFile = $resourceFactory->getFileObject($languageVariantFileUid);

                    // here, we make sure the files are actually different. When a language metadata is first created
                    // without uploading a file as languageVariant, the orginal file is copied to the languageVariant
                    // folder. We don't want to use that one.
                    $originalSha1 = $fileOrFolderObject->getSha1();
                    $languageVariantSha1 = $languageVariantFile->getSha1();
                    if ($originalSha1 != $languageVariantSha1) {
                        $fileOrFolderObject = $languageVariantFile;
                    }
                }
            }
        }


        $linkLocation = $fileOrFolderObject->getPublicUrl();
        if ($linkLocation === null) {
            // set the linkLocation to an empty string if null,
            // so it does not collide with the various string functions
            $linkLocation = '';
        }
        // Setting title if blank value to link
        $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, rawurldecode($linkLocation));

        $typoVersion = GeneralUtility::makeInstance(Typo3Version::class);
        if ($typoVersion->getMajorVersion() < 12) {
            // v11
            $url = $this->processUrl(UrlProcessorInterface::CONTEXT_FILE, $linkLocation, $conf) ?? '';
        } else {
            // >= v12
            $url = $linkLocation;
        }


        if (!empty($linkDetails['fragment'])) {
            $url .= '#' . $linkDetails['fragment'];
        }

        if ($typoVersion->getMajorVersion() < 12) {
            // v11
            return (new LinkResult($linkDetails['type'], $this->forceAbsoluteUrl($url, $conf)))
                ->withTarget($target ?: $this->resolveTargetAttribute($conf, 'fileTarget', false, $this->getTypoScriptFrontendController()->fileTarget))
                ->withLinkText($linkText);
        } else {
            // v12
            return (new LinkResult($linkDetails['type'], $this->forceAbsoluteUrl($url, $conf)))
                ->withLinkConfiguration($conf)
                ->withTarget($target ?: $this->resolveTargetAttribute($conf, 'fileTarget'))
                ->withLinkText($linkText);
        }
    }
}
