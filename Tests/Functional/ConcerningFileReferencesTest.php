<?php
declare(strict_types=1);

namespace T3G\AgencyPack\Tests\Functional;
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
class ConcerningFileReferencesTest extends FunctionalTestCase
{

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataSet/ConcerningFileReferences/Initial/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/DataSet/ConcerningFileReferences/AfterOperation/';


    /**
     * @test
     */
    public function deleteTranslatedMetadataResetsConsumingReferencesToDefaultFile()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 2, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'deleteMetadata';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        $this->actionService->deleteRecord('sys_file_metadata', 12);

        $this->importAssertCSVScenario($scenarioName);
    }
}
