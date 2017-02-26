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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Description
 */
class DataHandlerTest extends AbstractDataHandlerActionTestCase
{

    protected $testExtensionsToLoad = ['typo3conf/ext/file_variants'];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/Modify/DataSet/';

    protected function setUp()
    {
        parent::setUp();

        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(FileMetadataPermissionsAspect::class,
            $fileMetadataPermissionAspect->reveal());

        $this->importScenarioDataSet('initialSetup');
        $this->setUpFrontendRootPage(1);
        $this->backendUser->workspace = 0;
    }

    /**
     * @test
     */
    public function createNewSysFileRecordAndMetadataRecord()
    {
        $newFileId = $this->getUniqueId('NEW');
        $newSysFileRecord = [
            'pid' => 0,
            'missing' => 0,
            'storage' => 1,
            'identifier' => 'createNewSysFileRecordAndMetadataRecord',
            'name' => 'new_file.png'
        ];

        $newSysFileMetadataRecord = [
            'pid' => 0,
            'file' => $newFileId,
            'title' => 'title'
        ];

        $dataMap = [
            'sys_file' => [
                $newFileId => $newSysFileRecord
            ],
            'sys_file_metadata' => [
                $this->getUniqueId('NEW') => $newSysFileMetadataRecord
            ]
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');

        $fileRecord = $queryBuilder->select('*')->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('identifier',
                    $queryBuilder->createNamedParameter('createNewSysFileRecordAndMetadataRecord'))
            )
            ->execute()->fetchAll();

        $storage = $this->prophesize(ResourceStorage::class);
        $file = new File($fileRecord[0], $storage->reveal());
        $metadata = $file->_getMetaData();

        $this->assertEquals($metadata['file'], $fileRecord[0]['uid']);
    }

    /**
     * @test
     */
    public function localizeMetaData()
    {
        $this->importScenarioDataSet('sysFileAndMetaDataToBuildUpon');
        $this->actionService->localizeRecord('sys_file_metadata', 1, 1);
        $this->assertAssertionDataSet('metadataTranslation');
    }

    /**
     * @test
     */
    public function useFileInFalField()
    {
        $this->prepareDataSet();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');
        $files = $queryBuilder->select('*')->from('sys_file')->where(
            $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter('useFileInFalField'))
        )->execute()->fetchAll();

        $resourceFactory = ResourceFactory::getInstance();
        /** @var FileReference $reference */
        $reference = $resourceFactory->getFileReferenceObject($files[0]['uid']);
        $fileName = $reference->getOriginalFile()->getName();
        $ttContentUid = $reference->getReferenceProperty('uid_foreign');
        $this->assertEquals('new_file.png', $fileName);
        $this->assertEquals(1, $ttContentUid);
    }

    protected function prepareDataSet() {
        $ttContentRecordId = $this->getUniqueId('NEW');
        $ttContentRecord = [
            $ttContentRecordId => [
                'pid' => 1,
                'title' => 'my awesome record',
                'image' => '1'
            ]
        ];
        $fileId = $this->getUniqueId('NEW');
        $fileRecord = [
            $fileId => [
                'pid' => 0,
                'missing' => 0,
                'storage' => 1,
                'identifier' => 'useFileInFalField',
                'name' => 'new_file.png'
            ]
        ];
        unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);
        $referenceRecord = [
            $this->getUniqueId('NEW') => [
                'uid_local' => $fileId,
                'uid_foreign' => $ttContentRecordId,
                'pid' => 1
            ]
        ];
        $dataMap = [
            'tt_content' => $ttContentRecord,
            'sys_file' => $fileRecord,
            'sys_file_reference' => $referenceRecord
        ];
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
    }

    /**
     * @test
     */
    public function useFileInTranslatedRecord()
    {
        $this->prepareDataSet();

        $this->actionService->localizeRecord('tt_content', 1, 1);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $references = $queryBuilder->select('*')->from('sys_file_reference')->where(
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
        )->execute()->fetchAll();

        $this->assertEquals(1, $references[0]['uid_local']);
        $this->assertEquals(2, $references[0]['uid_foreign']);
    }

}
