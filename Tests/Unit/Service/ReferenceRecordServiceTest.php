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
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\RecordService;
use T3G\AgencyPack\FileVariants\Service\ReferenceRecordService;
use PHPUnit\Framework\TestCase;

class ReferenceRecordServiceTest extends TestCase
{

    /**
     * @var ReferenceRecordService
     */
    protected $subject;

    /**
     * @var PersistenceService|ObjectProphecy
     */
    protected $persistenceService;

    /**
     * @var RecordService|ObjectProphecy
     */
    protected $recordService;

    protected function setUp()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForInvalidFileToLookFor()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335146);
        $subject->updateReferences(0, 1, 1);
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForInvalidFileToReplaceWith()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335147);
        $subject->updateReferences(1, 0, 1);
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForDefaultLanguage()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335148);
        $subject->updateReferences(1, 1, 0);
    }

    /**
     * @test
     */
    public function findReferencesByUidForeignAndSysLanguageUidThrowsExceptionUponInvalidUidForeign()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489498930);
        $subject->findReferencesByUidForeignAndSysLanguageUid(0, 1, 'table');
    }

    /**
     * @test
     */
    public function findReferencesByUidForeignAndSysLanguageUidThrowsExceptionUponInvalidSysLanguageUid()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $recordService = $this->prophesize(RecordService::class);
        $subject = new ReferenceRecordService($persistenceService->reveal(), $recordService->reveal());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489498931);
        $subject->findReferencesByUidForeignAndSysLanguageUid(42, -1, 'table');
    }
}
