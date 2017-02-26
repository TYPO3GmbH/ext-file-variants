<?php
declare(strict_types = 1);
namespace T3G\AgencyPack\FileVariants\Tests\Functional\DataHandler;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
  * Description
  */
abstract class BaseTest extends AbstractDataHandlerActionTestCase {

    protected $testExtensionsToLoad = ['typo3conf/ext/file_variants'];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/Modify/DataSet/SysFileTranslatable/';

    protected function setUp()
    {
        parent::setUp();

        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(FileMetadataPermissionsAspect::class, $fileMetadataPermissionAspect->reveal());

        $this->importScenarioDataSet('initialSetup');
        $this->setUpFrontendRootPage(1);
        $this->backendUser->workspace = 0;

        // done to prevent an error during processing
        // it makes no difference here whether file filters apply to the data set
        unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);
    }

    /**
     * helper method to get new data into DB
     */
    protected function prepareDataSet()
    {

        $newFileId = $this->getUniqueId('NEW');
        $newSysFileRecord = [
            $newFileId => [
                'pid' => 0,
                'type' => 2,
                'missing' => 0,
                'mime_type' => 'image/png',
                'sha1' => '--',
                'storage' => 1,
                'identifier' => 'first file',
                'name' => 'first_file.png',
                'size' => 474600,
            ]
        ];

        $newSysFileMetadataRecord = [
            $this->getUniqueId('NEW') => [
                'pid' => 0,
                'file' => $newFileId,
                'title' => 'title for first file',
                'l10n_diffsource' => '--',
            ]
        ];

        $ttContentRecordId = $this->getUniqueId('NEW');
        $ttContentRecord = [
            $ttContentRecordId => [
                'pid' => 1,
                'title' => 'my awesome record',
                'image' => '1'
            ]
        ];

        $referenceRecord = [
            $this->getUniqueId('NEW') => [
                'uid_local' => $newFileId,
                'uid_foreign' => $ttContentRecordId,
                'pid' => 1
            ]
        ];
        $dataMap = [
            'tt_content' => $ttContentRecord,
            'sys_file' => $newSysFileRecord,
            'sys_file_metadata' => $newSysFileMetadataRecord,
            'sys_file_reference' => $referenceRecord
        ];
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
    }

}
