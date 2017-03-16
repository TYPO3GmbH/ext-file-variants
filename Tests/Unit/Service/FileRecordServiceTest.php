<?php
declare(strict_types = 1);
namespace T3G\AgencyPack\FileVariants\Tests\Unit\Service;

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
use Prophecy\Prophecy\ObjectProphecy;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\ReferenceRecordService;

class FileRecordServiceTest extends TestCase
{
    /**
     * @test
     */
    public function copyFileThrowsExceptionForInvalidLanguage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334111);

        $persistenceService = $this->prophesize(PersistenceService::class);
        $referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $subject = new FileRecordService($persistenceService->reveal(), $referenceRecordService->reveal());
        $subject->translateSysFileRecord(22, 0);
    }

    /**
     * @test
     */
    public function copyFileThrowsExceptionForNonValidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334112);

        $persistenceService = $this->prophesize(PersistenceService::class);
        $referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $subject = new FileRecordService($persistenceService->reveal(), $referenceRecordService->reveal());
        $subject->translateSysFileRecord(0, 1);
    }

    /**
     * @test
     */
    public function updateMetadataThrowsExceptionForInvalidRecord()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334113);

        $persistenceService = $this->prophesize(PersistenceService::class);
        $referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $subject = new FileRecordService($persistenceService->reveal(), $referenceRecordService->reveal());
        $subject->updateSysFileMetadata(0, 18);
    }

    /**
     * @test
     */
    public function updateMetadataThrowsExceptionForInvalidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334114);

        $persistenceService = $this->prophesize(PersistenceService::class);
        $referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $subject = new FileRecordService($persistenceService->reveal(), $referenceRecordService->reveal());
        $subject->updateSysFileMetadata(18, -1);
    }
}
