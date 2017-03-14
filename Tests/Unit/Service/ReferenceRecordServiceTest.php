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
        $this->persistenceService = $this->prophesize(PersistenceService::class);
        $this->recordService = $this->prophesize(RecordService::class);
        $this->subject = new ReferenceRecordService($this->persistenceService->reveal(), $this->recordService->reveal());
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForInvalidFileToLookFor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335146);
        $this->subject->updateReferences(0, 1, 1);
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForInvalidFileToReplaceWith()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335147);
        $this->subject->updateReferences(1, 0, 1);
    }

    /**
     * @test
     */
    public function updateReferencesThrowsExceptionForDefaultLanguage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489335148);
        $this->subject->updateReferences(1, 1, 0);
    }

    /**
     * @test
     */
    public function updateReferencesHandlesAffectedReferences()
    {
        $references = [
            ['uid' => 7],
            ['uid' => 14],
            ['uid' => 21],
        ];
        $this->persistenceService->collectAffectedReferences(1, 3)->willReturn($references);
        $filteredReferences = [7,21];
        $this->recordService->filterValidReferences($references)->willReturn($filteredReferences);
        $this->persistenceService->updateReferences($filteredReferences, 2)->shouldBeCalled();
        $this->subject->updateReferences(1, 2, 3);
    }

    /**
     * @test
     */
    public function findReferencesByUidForeignAndSysLanguageUidThrowsExceptionUponInvalidUidForeign()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489498930);
        $this->subject->findReferencesByUidForeignAndSysLanguageUid(0, 1, 'table');
    }

    /**
     * @test
     */
    public function findReferencesByUidForeignAndSysLanguageUidThrowsExceptionUponInvalidSysLanguageUid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1489498931);
        $this->subject->findReferencesByUidForeignAndSysLanguageUid(42, -1, 'table');
    }
}
