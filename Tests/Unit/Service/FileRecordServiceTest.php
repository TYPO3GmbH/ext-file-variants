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

use Prophecy\Prophecy\ObjectProphecy;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use PHPUnit\Framework\TestCase;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;

class FileRecordServiceTest extends TestCase
{

    /**
     * @var FileRecordService
     */
    protected $subject;

    /**
     * @var PersistenceService|ObjectProphecy
     */
    protected $persistenceService;

    protected function setUp()
    {
        $this->persistenceService = $this->prophesize(PersistenceService::class);
        $this->subject = new FileRecordService($this->persistenceService->reveal());
    }

    /**
     * @test
     */
    public function copyFileThrowsExceptionForInvalidLanguage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334111);
        $this->subject->copySysFileRecord(22, 0);
    }

    /**
     * @test
     */
    public function copyFileThrowsExceptionForNonValidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334112);
        $this->subject->copySysFileRecord(0, 1);
    }

    /**
     * @test
     */
    public function updateMetadataThrowsExceptionForInvalidRecord()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334113);
        $this->subject->updateSysFileMetadata(0, 18);
    }

    /**
     * @test
     */
    public function updateMetadataThrowsExceptionForInvalidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334114);
        $this->subject->updateSysFileMetadata(18, -1);
    }

    /**
     * @test
     */
    public function copyFileReturnsUidOfCopiedFileRecord()
    {
        $cmdMap = [
            'sys_file' => [
                28 => [
                    'localize' => 1
                ]
            ]
        ];
        $this->persistenceService->process_cmdMap($cmdMap)->shouldBeCalled();
        $this->persistenceService->getSysFileRecord(28, 1)->willReturn(['uid' => 30]);
        $result = $this->subject->copySysFileRecord(28, 1);
        $this->assertSame(30, $result);
    }


}
