<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Tests\Functional;

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
use Prophecy\PhpUnit\ProphecyTrait;
use T3G\AgencyPack\FileVariants\Tests\Functional\DataHandling\PermissiveActionService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;

/**
  * extending typo3/testing-framework basic FunctionalTestCase
  */
abstract class FunctionalTestCase extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = '';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = '';

    /**
     * @var int
     */
    protected $expectedErrorLogEntries = 0;
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;
    /**
     * @var PermissiveActionService
     */
    protected $actionService;

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'fluid';
        $this->testExtensionsToLoad[] = 'typo3conf/ext/file_variants';

        $this->pathsToLinkInTestInstance['typo3conf/ext/file_variants/Tests/Functional/Fixture/Sites'] = 'typo3conf/sites';

        parent::setUp();

        // make sure there are no leftover files from earlier tests
        // done in setup because teardown is called only once per file
        if (file_exists(Environment::getPublicPath() . '/languageVariants')) {
            system('rm -rf ' . escapeshellarg(Environment::getPublicPath() . '/languageVariants'));
        }

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->importCSVDataSet(__DIR__ . '/Fixture/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);

        $fileMetadataPermissionAspect = $this->prophesize(FileMetadataPermissionsAspect::class);
        GeneralUtility::setSingletonInstance(
            FileMetadataPermissionsAspect::class,
            $fileMetadataPermissionAspect->reveal()
        );

        $this->actionService = new PermissiveActionService();

        // done to prevent an error during processing
        // it makes no difference here whether file filters apply to the data set
        unset($GLOBALS['TCA']['tt_content']['columns']['image']['config']['filter']);

        // set up the second file storage
        mkdir(Environment::getPublicPath() . '/languageVariants/languageVariants', 0777, true);
        mkdir(Environment::getPublicPath() . '/languageVariants/_processed_', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanUpFilesAndRelatedRecords();
        unset($this->actionService);
        $this->assertErrorLogEntries();
        parent::tearDown();
    }

    /**
     * remove files and related records (sys_file, sys_file_metadata) from environment
     */
    protected function cleanUpFilesAndRelatedRecords()
    {
        // find all storages used
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
        $result = $queryBuilder->select('*')->from('sys_file_storage')->executeQuery();
        while ($storageUid = $result->fetchAssociative()['uid']) {
            // find files in storage
            $storage = GeneralUtility::makeInstance(ResourceFactory::class)->getStorageObject($storageUid);
            $recordsToDelete = ['sys_file' => [], 'sys_file_metadata' => []];
            try {
                $folder = $storage->getFolder('languageVariants');
                $files = $storage->getFilesInFolder($folder);
                foreach ($files as $file) {
                    $storage->deleteFile($file);
                    $recordsToDelete['sys_file'][] = $file->getUid();
                    $fileMetadata = $file->getMetaData()->get();
                    $recordsToDelete['sys_file_metadata'][] = (int)$fileMetadata['uid'];
                }
            } catch (\Exception) {
                // sometimes, there is no folder to empty. Let's ignore that.
            }
            $this->actionService->deleteRecords($recordsToDelete);
        }
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
            ->from('sys_log')->where($queryBuilder->expr()->in(
            'error',
            $queryBuilder->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY)
        ))->executeQuery();

        $actualErrorLogEntries = $statement->rowCount();
        if ($actualErrorLogEntries === $this->expectedErrorLogEntries) {
            $this->assertSame($this->expectedErrorLogEntries, $actualErrorLogEntries);
        } else {
            $failureMessage = 'Expected ' . $this->expectedErrorLogEntries . ' entries in sys_log, but got ' . $actualErrorLogEntries . LF;
            while ($entry = $statement->fetchAssociative()) {
                $entryData = unserialize($entry['log_data']);
                $entryMessage = vsprintf($entry['details'], $entryData);
                $failureMessage .= '* ' . $entryMessage . LF;
            }
            $this->fail($failureMessage);
        }
    }

    protected function importCsvScenario(string $scenarioName = '')
    {
        $scenarioFileName = $this->scenarioDataSetDirectory . $scenarioName . '.csv';
        $scenarioFileName = GeneralUtility::getFileAbsFileName($scenarioFileName);
        $this->importCSVDataSet($scenarioFileName);
    }

    protected function importAssertCSVScenario(string $scenarioName = '')
    {
        $scenarioFileName = $this->assertionDataSetDirectory . $scenarioName . '.csv';
        $scenarioFileName = GeneralUtility::getFileAbsFileName($scenarioFileName);
        $this->assertCSVDataSet($scenarioFileName);
    }
}
