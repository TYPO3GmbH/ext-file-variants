<?php

namespace T3G\AgencyPack\FileVariants\Service;

use PHPUnit\Framework\TestCase;

class ResourcesServiceTest extends TestCase
{

    /**
     * @test
     */
    public function prepareFileStorageEnvironmentThrowsExceptionForNotAvailableStorageUid()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490480372);

        $config = [
            'variantsStorageUid' => 42,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize($config);

        $subject = new ResourcesService();
        $subject->prepareFileStorageEnvironment();
    }
}
