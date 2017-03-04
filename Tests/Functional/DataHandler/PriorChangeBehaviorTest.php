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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\Components\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Description
 */
class PriorChangeBehaviorTest extends FunctionalTestCase
{

    /**
     * @var ActionService
     */
    protected $actionService;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

    protected $testExtensionsToLoad = ['typo3conf/ext/file_variants'];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/PriorChange/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/PriorChange/';

    protected function setUp()
    {
        parent::setUp();
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(FileMetadataPermissionsAspect::class, $fileMetadataPermissionAspect->reveal());

        $this->importCsvScenario('initialSetup');
        $this->setUpFrontendRootPage(1);

        $this->actionService = new ActionService();

        // done to prevent an error during processing
        // it makes no difference here whether file filters apply to the data set
        unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);
    }

    protected function tearDown()
    {
        unset($this->actionService);
        parent::tearDown();
    }

    /**
     * @param string $scenarioName
     */
    protected function importCsvScenario(string $scenarioName = '')
    {
        $scenarioFileName = $this->scenarioDataSetDirectory . $scenarioName . '.csv';
        $scenarioFileName = GeneralUtility::getFileAbsFileName($scenarioFileName);
        $this->importCSVDataSet($scenarioFileName);
    }

    /**
     * @param string $scenarioName
     */
    protected function importAssertCSVScenario(string $scenarioName = '')
    {
        $scenarioFileName = $this->assertionDataSetDirectory . $scenarioName . '.csv';
        $scenarioFileName = GeneralUtility::getFileAbsFileName($scenarioFileName);
        $this->assertCSVDataSet($scenarioFileName);
    }

    /**
     * @test
     */
    public function createNewSysFileRecordAndMetadataRecord()
    {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file');

        $fileRecord = $queryBuilder->select('*')->from('sys_file')
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
        $this->markTestSkipped('this is now broken, thanks to DataHandlerHook Scenario 1');
        $this->actionService->localizeRecord('sys_file_metadata', 1, 1);
        $this->importAssertCSVScenario('metadataTranslation');
    }

    /**
     * @test
     */
    public function useFileInFalField()
    {

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $references = $queryBuilder->select('*')->from('sys_file_reference')->execute()->fetchAll();

        $this->assertEquals(1, $references[0]['uid_local']);
        $this->assertEquals(1, $references[0]['uid_foreign']);
    }

    /**
     * @test
     */
    public function useFileInTranslatedRecord()
    {
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
