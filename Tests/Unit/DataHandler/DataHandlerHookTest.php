<?php
declare(strict_types=1);

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

use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Class DataHandlerHookTest
 * @package T3G\AgencyPack\FileVariants\Tests\Unit\DataHandler
 */
class DataHandlerHookTest extends TestCase
{

    /**
     * @test
     */
    public function constructThrowsExceptionIfNoConfigurationCanBeFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1490476773);
        new DataHandlerHook();
    }

    /**
     * @test
     */
    public function hookThrowsExceptionIfNoValidIdIsFound()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'] = serialize(['foo']);
        $subject = new DataHandlerHook();
        /** @var DataHandler $dataHandler */
        $dataHandler = $this->prophesize(DataHandler::class);
        $dataHandler->substNEWwithIDs = [];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489332067);
        $subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 'NEW_42', 'foo', $dataHandler->reveal());
    }

}
