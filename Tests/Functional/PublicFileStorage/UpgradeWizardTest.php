<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Tests\Functional\PublicFileStorage;

use T3G\AgencyPack\FileVariants\Tests\Functional\FunctionalTestCase;
use T3G\AgencyPack\FileVariants\Updates\MetaDataRecordsUpdateWizard;

/**
 * Class UpgradeWizardTest
 */
class UpgradeWizardTest extends FunctionalTestCase
{

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/PublicFileStorage/DataSet/UpgradeWizard/Initial/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/PublicFileStorage/DataSet/UpgradeWizard/AfterOperation/';

    /**
     * @test
     */
    public function runWizard()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['variantsStorageUid' => 5, 'variantsFolder' => 'languageVariants']);
        $scenarioName = 'UpgradeWizard';
        $this->importCsvScenario($scenarioName);
        $this->setUpFrontendRootPage(1);

        copy(PATH_site . 'typo3conf/ext/file_variants/Tests/Functional/Fixture/TestFiles/cat_1.jpg', PATH_site . 'fileadmin/cat_1.jpg');

        $subject = new MetaDataRecordsUpdateWizard();
        $dbQueries = [];
        $customMessage = '';
        $subject->performUpdate($dbQueries, $customMessage);

        $this->importAssertCSVScenario($scenarioName);
    }
}
