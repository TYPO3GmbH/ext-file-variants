<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Tests\Unit\DataHandler;

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

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DataHandlerHookTest
 */
class DataHandlerHookTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function constructThrowsExceptionIfNoConfigurationCanBeFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490476773);

        $extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $extensionConfiguration->get('file_variants')->willThrow(ExtensionConfigurationExtensionNotConfiguredException::class);
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionConfiguration->reveal());

        new DataHandlerHook();
    }

    /**
     * @test
     */
    public function hookThrowsExceptionIfNoValidIdIsFound()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['file_variants'] = ['foo'];
        $subject = new DataHandlerHook();
        $dataHandler = $this->prophesize(DataHandler::class);
        $dataHandler->reveal()->substNEWwithIDs = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489332067);
        $subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 'NEW_42', 'foo', $dataHandler->reveal());
    }
}
