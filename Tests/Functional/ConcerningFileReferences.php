<?php

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

use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConcerningFileReferences
 */
class ConcerningFileReferences extends FunctionalTestCase
{

    /**
     * @test
     */
    public function deleteTranslatedMetadataResetsConsumingReferencesToDefaultFile()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];

        $scenarioName = 'deleteMetadata';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject(12);

        $request = (new ServerRequestFactory())
            ->createServerRequest('get', 'http://localhost/index.php')
            ->withQueryParams([
                'data' => [
                    'delete' => [
                        [
                            'data' => $file->getUid(),
                        ],
                    ],
                ],
            ]);
        /** @var FileController $fileController */
        $fileController = GeneralUtility::makeInstance(FileController::class);
        $fileController->mainAction($request);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translateMetadataUpdatesConsumingReferences()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'translateMetadata';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingConsumingRecordInConnectedModeProvidesLanguageVariantForLanguage()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'translateConsumingRecord';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_4.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_4.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/nature_1.jpg', Environment::getPublicPath() . '/fileadmin/nature_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/nature_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/nature_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/nature_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/nature_3.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/nature_4.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/nature_4.jpg');
        $this->actionService->localizeRecord('tt_content', 1, 1);
        $this->actionService->localizeRecord('tt_content', 1, 2);
        $this->actionService->localizeRecord('tt_content', 1, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingConsumingRecordInFreeModeProvidesLanguageVariantForLanguage()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'translateConsumingFreeModeRecord';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_4.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_4.jpg');
        $this->actionService->copyRecordToLanguage('tt_content', 1, 1);
        $this->actionService->copyRecordToLanguage('tt_content', 1, 2);
        $this->actionService->copyRecordToLanguage('tt_content', 1, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingConsumingRecordFromNonDefaultLanguageProvidesLanguageVariant()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'translateFromNonDefaultLanguage';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_4.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_4.jpg');
        $this->actionService->localizeRecord('tt_content', 1, 1);
        $this->actionService->localizeRecord('tt_content', 2, 2);
        $this->actionService->localizeRecord('tt_content', 3, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function copyConsumingRecordFromNonDefaultLanguageProvidesLanguageVariant()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'copyFromNonDefaultLanguage';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_4.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_4.jpg');
        $this->actionService->copyRecordToLanguage('tt_content', 1, 1);
        $this->actionService->copyRecordToLanguage('tt_content', 2, 2);
        $this->actionService->copyRecordToLanguage('tt_content', 3, 3);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translatingConsumingRecordThatIsNotTtContentWorksLikeConnectedMode()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants'];
        $scenarioName = 'translateConsumingRecordNotTtContent';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', Environment::getPublicPath() . '/fileadmin/cat_1.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_2.jpg');
        copy(Environment::getPublicPath() . '/typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', Environment::getPublicPath() . '/languageVariants/languageVariants/cat_3.jpg');
        $this->actionService->localizeRecord('sys_file_collection', 1, 1);
        $this->actionService->copyRecordToLanguage('sys_file_collection', 2, 2);

        $this->importAssertCSVScenario($scenarioName);
    }
}
