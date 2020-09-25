<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = $config;

        $subject = new ResourcesService();
        $subject->prepareFileStorageEnvironment();
    }
}
