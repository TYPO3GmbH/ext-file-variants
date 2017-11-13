<?php
declare(strict_types=1);

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
use T3G\AgencyPack\FileVariants\Controller\FileVariantsController;
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use TYPO3\CMS\Backend\Controller\File\FileController;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConcerningMetadata
 * @package T3G\AgencyPack\FileVariants\Tests\Functional\PublicFileStorage
 */
class ConcerningMetadata extends FunctionalTestCase
{

    /**
     * @test
     */
    public function exceptionIsThrownForBadStorageConfiguration()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 42]);
        $subject = new DataHandlerHook();
        /** @var DataHandler $dataHandler */
        $dataHandler = $this->prophesize(DataHandler::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490480372);
        $subject->processCmdmap_postProcess('localize', 'sys_file_metadata', '1', 'foo', $dataHandler->reveal());
    }

    /**
     * @test
     */
    public function defaultStorageIsUsedIfNoneIsConfigured()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 0, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'defaultStorage';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'fileadmin/cat_1.jpg');
        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function configuredStorageIsUsed()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'configuredStorage';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'fileadmin/cat_1.jpg');
        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function translationOfMetadataCreatesLocalizedFileRecord()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'translateMetadata';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'fileadmin/cat_1.jpg');
        $this->actionService->localizeRecord('sys_file_metadata', 11, 1);

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function uploadingVariantReplacesFileWithoutChangingUid()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'provideFileVariant';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $controller = new FileVariantsController();
        $request = new ServerRequest();

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'languageVariants/languageVariants/cat_1.jpg');

        @mkdir(PATH_site . 'typo3temp/file_variants_uploads/', 0777, true);
        $localFilePath = PATH_site . 'typo3temp/file_variants_uploads/cat_2.jpg';
        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', $localFilePath);

        $storage = ResourceFactory::getInstance()->getStorageObject(2);
        $folder = $storage->getFolder('languageVariants');
        $newFile = $storage->addFile($localFilePath, $folder);
        $request = $request->withQueryParams(['file' => $newFile->getUid(), 'uid' => 12]);
        $controller->ajaxUploadFileVariant($request, new Response());

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function replacingVariantReplacesFileWithoutChangingUid()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'replaceFileVariant';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $controller = new FileVariantsController();
        $request = new ServerRequest();

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', PATH_site . 'languageVariants/languageVariants/cat_2.jpg');

        @mkdir(PATH_site . 'typo3temp/file_variants_uploads/', 0777, true);
        $localFilePath = PATH_site . 'typo3temp/file_variants_uploads/cat_3.jpg';
        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', $localFilePath);

        $storage = ResourceFactory::getInstance()->getStorageObject(2);
        $folder = $storage->getFolder('languageVariants');
        $newFile = $storage->addFile($localFilePath, $folder);
        $request = $request->withQueryParams(['file' => $newFile->getUid(), 'uid' => 12]);
        $controller->ajaxReplaceFileVariant($request, new Response());

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function resetVariantReplacesFileWithoutChangingUid()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'resetFileVariant';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $controller = new FileVariantsController();
        $request = new ServerRequest();

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'languageVariants/languageVariants/cat_1.jpg');
        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_2.jpg', PATH_site . 'languageVariants/languageVariants/cat_2.jpg');

        $request = $request->withQueryParams(['uid' => 12]);
        $controller->ajaxResetFileVariant($request, new Response());

        $this->importAssertCSVScenario($scenarioName);
    }

    /**
     * @test
     */
    public function fileDeletionRemovesAllRelatedFilesAndMetadata()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'] = true;

        $scenarioName = 'deleteMetadata';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_3.jpg', PATH_site . 'languageVariants/languageVariants/cat_3.jpg');
        $file = ResourceFactory::getInstance()->getFileObject(12);

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_GET = ['file' => [
            'delete' => [
                [
                    'data' =>
                        $file->getUid()
                ]
            ]
        ]
        ];
        $request = ServerRequestFactory::fromGlobals();
        $response = GeneralUtility::makeInstance(Response::class);
        /** @var FileController $fileController */
        $fileController = GeneralUtility::makeInstance(FileController::class);
        $fileController->mainAction($request, $response);

        $this->importAssertCSVScenario($scenarioName);
    }
}
