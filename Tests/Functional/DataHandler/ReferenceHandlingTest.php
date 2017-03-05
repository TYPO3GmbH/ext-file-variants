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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
  * Description
  */
class ReferenceHandlingTest extends FunctionalTestCase {

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
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/ReferenceHandling/Initial/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataHandler/DataSet/ReferenceHandling/Result/';

    protected function setUp()
    {

        $this->testExtensionsToLoad[] = 'typo3conf/ext/file_variants';

        parent::setUp();
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(FileMetadataPermissionsAspect::class, $fileMetadataPermissionAspect->reveal());

        $this->actionService = new ActionService();

        // done to prevent an error during processing
        // it makes no difference here whether file filters apply to the data set
        //unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);

    }

    protected function tearDown()
    {
        unset($this->actionService);
        $this->assertErrorLogEntries();
        parent::tearDown();

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
    public function translatedReferenceInConnectedModeRelatesToFileVariant()
    {
        // setup
        $scenarioName = 'connectedWithVariant';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->localizeRecord('tt_content', 1, 1);
        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatedReferenceInConnectedModeRelatesToDefaultFileIfNoVariantExists()
    {
        // setup
        $scenarioName = 'connectedWithoutVariant';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->localizeRecord('tt_content', 3, 1);
        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatedReferenceInFreeModeRelatesToDefaultFile()
    {
        // setup
        $scenarioName = 'freeMode';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->copyRecordToLanguage('tt_content', 1, 1);
        $this->importAssertCSVScenario($scenarioName);
    }

}
