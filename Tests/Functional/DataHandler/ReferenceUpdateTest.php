<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
  * Description
  */
class ReferenceUpdateTest extends FunctionalTestCase {

    /**
     * @var int
     */
    protected $expectedErrorLogEntries = 0;

    /**
     * @var ActionService
     */
    protected $actionService;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/ReferenceUpdate/Initial/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/ReferenceUpdate/AfterOperation/';

    protected function setUp()
    {
        $this->testExtensionsToLoad[] = 'typo3conf/ext/file_variants';
        $this->testExtensionsToLoad[] = 'typo3conf/ext/news';

        parent::setUp();

        // make sure there are no leftover files from earlier tests
        // done in setup because teardown is called only once per file
        if (file_exists(PATH_site . 'languageVariants')) {
            system('rm -rf ' . escapeshellarg(PATH_site . 'languageVariants'));
        }

        Bootstrap::getInstance()->initializeLanguageObject();

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(FileMetadataPermissionsAspect::class, $fileMetadataPermissionAspect->reveal());

        $this->actionService = new ActionService();

        // done to prevent an error during processing
        // it makes no difference here whether file filters apply to the data set
        unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);

        // set up the second file storage
        mkdir(PATH_site . 'languageVariants/languageVariants', 0777, true);
        mkdir(PATH_site . 'languageVariants/_processed_', 0777, true);
    }

    protected function tearDown()
    {
        $this->cleanUpFilesAndRelatedRecords();
        unset($this->actionService);
        $this->assertErrorLogEntries();
        parent::tearDown();
    }

    /**
     * remove files and related records (sys_file, sys_file_metadata) from environment
     */
    protected function cleanUpFilesAndRelatedRecords() {
        // find files in storage
        $storage = ResourceFactory::getInstance()->getStorageObject(2);
        $recordsToDelete = ['sys_file' => [], 'sys_file_metadata' => []];
        try {
            $folder = $storage->getFolder('languageVariants');
            $files = $storage->getFilesInFolder($folder);
            foreach ($files as $file) {
                $storage->deleteFile($file);
                $recordsToDelete['sys_file'][] = $file->getUid();
                $metadata = $file->_getMetaData();
                $recordsToDelete['sys_file_metadata'][] = (int)$metadata['uid'];
            }
        } catch (\Exception $exception) {
            // sometimes, there is no folder to empty. Let's ignore that.
        }
        $this->actionService->deleteRecords($recordsToDelete);
    }

    /**
     * Asserts correct number of warning and error log entries.
     *
     * @return void
     */
    protected function assertErrorLogEntries()
    {
        if ($this->expectedErrorLogEntries === null) {
            return;
        }
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('sys_log');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->in(
                    'error',
                    $queryBuilder->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY)
                )
            )
            ->execute();

        $actualErrorLogEntries = $statement->rowCount();
        if ($actualErrorLogEntries === $this->expectedErrorLogEntries) {
            $this->assertSame($this->expectedErrorLogEntries, $actualErrorLogEntries);
        } else {
            $failureMessage = 'Expected ' . $this->expectedErrorLogEntries . ' entries in sys_log, but got ' . $actualErrorLogEntries . LF;
            while ($entry = $statement->fetch()) {
                $entryData = unserialize($entry['log_data']);
                $entryMessage = vsprintf($entry['details'], $entryData);
                $failureMessage .= '* ' . $entryMessage . LF;
            }
            $this->fail($failureMessage);
        }
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
    public function providingFileVariantCausesUpdateOfAllCEsInConnectedMode()
    {
        $scenarioName = 'ConnectedMode_ProvideFileVariants';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site .'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/cat_1.JPG', PATH_site . 'fileadmin/cat_1.jpg');

        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);
        $this->actionService->localizeRecord('sys_file_metadata', 11, 2);
        $this->actionService->localizeRecord('sys_file_metadata', 11, 3);

        $this->importAssertCSVScenario($scenarioName);

    }

    /**
     * @test
     */
    public function providingFileVariantDoesNotTouchAllCEsInFreeMode()
    {
        $scenarioName = 'FreeMode_ProvideFileVariants';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site .'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/cat_1.JPG', PATH_site . 'fileadmin/cat_1.jpg');

        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);
        $this->actionService->localizeRecord('sys_file_metadata', 11, 2);
        $this->actionService->localizeRecord('sys_file_metadata', 11, 3);

        $this->importAssertCSVScenario($scenarioName);

    }

    /**
     * @test
     */
    public function providingFileVariantWithFileReplacementDoesNotChangeTheReferencedFile()
    {

        $scenarioName = 'ConnectedMode_ProvideFileVariantsWithReplacement';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site .'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/cat_1.JPG', PATH_site . 'fileadmin/cat_1.jpg');

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 1);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/nature_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 2);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/business_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 3);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/city_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingInFreeModeDoesNotUseVariants()
    {
        $scenarioName = 'FreeMode_TranslateCEs';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->copyRecordToLanguage('tt_content', 4, 1);
        $this->actionService->copyRecordToLanguage('tt_content', 4, 2);
        $this->actionService->copyRecordToLanguage('tt_content', 4, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingInConnectedModeUsesVariants()
    {
        $scenarioName = 'ConnectedMode_TranslateCEs';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->localizeRecord('tt_content', 4, 1);
        $this->actionService->localizeRecord('tt_content', 4, 2);
        $this->actionService->localizeRecord('tt_content', 4, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * use tx_news records to test for non pages|tt_content records
     *
     * @test
     */
    public function providingFileVariantCausesUpdateOfFALConsumingTableItemReferences()
    {
        $scenarioName = 'FALConsumingTable_ProvideFileVariants';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(2);

        copy(PATH_site .'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/cat_1.JPG', PATH_site . 'fileadmin/cat_1.jpg');

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 1);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/nature_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 2);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/business_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $ids = $this->actionService->localizeRecord('sys_file_metadata', 11, 3);
        $testFilePath = 'typo3conf/ext/file_variants/Tests/Fixture/TestFiles/city_1.jpg';
        list($filename, $postFiles) = $this->actionService->simulateUploadedFileArray('sys_file_metadata', (int)$ids['sys_file_metadata'][11], $testFilePath);
        $this->actionService->modifyRecord('sys_file_metadata', (int)$ids['sys_file_metadata'][11], ['language_variant' => $filename], null, $postFiles);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingOfFALConsumingTableItemsUsesVariants()
    {
        $scenarioName = 'FALConsumingTable_Translate';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(2);

        $this->actionService->localizeRecord('tx_news_domain_model_news', 1, 1);
        $this->actionService->localizeRecord('tx_news_domain_model_news', 1, 2);
        $this->actionService->localizeRecord('tx_news_domain_model_news', 1, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    public function deletionOfFileVariantResetsAllConsumersInConnectedModeToDefaultFile()
    {

    }

    public function deletionOfFileVariantDoesNotTouchAllConsumersInFreeMode()
    {

    }

    public function deletionOfDefaultFileCausesResetToDefaultFileForAllTranslations()
    {
        // remove default file -> remove variants -> update consumers to relate to default file
        // leads to broken relations, this is the case already before the change.
    }

    public function additionOfReferenceToTranslatedConnectedModeCEUsesVariant()
    {

    }

    public function additionOfReferenceToTranslatedFreeModeCEDoesNotUseVariant()
    {

    }

    public function additionOfReferenceToFALConsumingTableItemUsesVariantsInTranslations()
    {

    }
}
