<?php
declare(strict_types=1);
namespace T3G\AgencyPack\FileVariants\Tests\Unit;
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
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use T3G\AgencyPack\FileVariants\Service\RecordService;
use T3G\AgencyPack\FileVariants\Service\ReferenceRecordService;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Class DataHandlerHookTest
 * @package T3G\AgencyPack\FileVariants\Tests\Unit
 */
class DataHandlerHookTest extends TestCase
{
    /**
     * @test
     */
    public function hookThrowsExceptionIfNoValidIdCanBeFound()
    {
        $fileRecordService = $this->prophesize(FileRecordService::class);
        $referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new DataHandlerHook($fileRecordService->reveal(), $referenceRecordService->reveal(), $persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489332067);
        $subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 'NEW123456', 1, $this->prophesize(DataHandler::class)->reveal());
    }
}
