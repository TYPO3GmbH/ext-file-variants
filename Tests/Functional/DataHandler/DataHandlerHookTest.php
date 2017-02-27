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

/**
  * Description
  */
class DataHandlerHookTest extends BaseTest {

    /**
     * @test
     */
    public function translationOfMetadataWithoutNewFileVariantCopiesAndRelatesDefaultFile()
    {
        $this->actionService->localizeRecord('sys_file_metadata', 1, 1);
        $this->assertAssertionDataSet('metadataTranslationWithoutVariantUpload');
    }

    /**
     * @test
     */
    public function translationOfMetaDataCreatesTranslatedSysFileRecord () {
        $this->actionService->localizeRecord('sys_file_metadata', 1, 1);
        //@todo simulate upload of new file into translated metadata record (will end up in sys_file)
        $this->assertAssertionDataSet('metadataTranslationWithVariantUpload');
    }

    /**
     * @test
     */
    public function translatedReferenceInConnectedModeRelatesToFileVariant()
    {
        $this->actionService->localizeRecord('sys_file', 1, 1);
        $this->actionService->localizeRecord('sys_file_metadata', 1 ,1);
        $this->actionService->localizeRecord('tt_content', 1, 1);
        $this->assertAssertionDataSet('ttContentTranslatedConnectedMode');
    }

    public function translatedReferenceInConnectedModeRelatesToDefaultFileIfNoVariantExists()
    {

    }

    public function translatedReferenceInFreeModeRelatesToDefaultFile()
    {

    }

    public function providingFileVariantCausesUpdateOfAllConsumersInConnectedMode()
    {

    }

    public function providingFileVariantDoesNotTouchAllConsumersInFreeMode()
    {

    }

    public function deletionOfFileVariantResetsAllConsumersInConnectedModeToDefaultFile()
    {

    }

    public function deletionOfFileVariantDoesNotTouchAllConsumersInConnectedMode()
    {

    }

    public function deletionOfDefaultFileCausesResetToDefaultFileForAllTranslations()
    {
        // remove default file -> remove variants -> update consumers to relate to default file
        // leads to broken relations, this is the case already before the change.
    }
}
