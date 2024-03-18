<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Service;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResourcesServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function prepareFileStorageEnvironmentThrowsExceptionForNotAvailableStorageUid()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490480372);

        $config = [
            'variantsStorageUid' => 42,
            'variantsFolder' => '/languageVariants',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = $config;

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        $resourceFactory->getStorageObject(42)->willThrow(\InvalidArgumentException::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $subject = new ResourcesService();
        $subject->prepareFileStorageEnvironment();
    }
}
