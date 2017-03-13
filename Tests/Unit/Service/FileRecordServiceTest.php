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

use Prophecy\Argument;
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
        $this->subject->translateSysFileRecord(22, 0);
    }

    /**
     * @test
     */
    public function copyFileThrowsExceptionForNonValidFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489334112);
        $this->subject->translateSysFileRecord(0, 1);
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
        $this->persistenceService->getFileObject(28)->shouldBeCalled();
        $this->persistenceService->findStorageDestination()->shouldBeCalled();
        $this->persistenceService->copyFileObject(Argument::cetera())->willReturn(30);
        $dataMap = [
            'sys_file' => [
                30 => [
                    'sys_language_uid' => 1,
                    'l10n_parent' => 28
                ]
            ]
        ];
        $this->persistenceService->process_dataMap($dataMap)->shouldBeCalled();
        $result = $this->subject->translateSysFileRecord(28, 1);
        $this->assertSame(30, $result);
    }

    public function updateSysFileRecordReplacesFile()
    {

    }

}
