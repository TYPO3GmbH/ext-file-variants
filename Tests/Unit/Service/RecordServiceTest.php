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
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\RecordService;

class RecordServiceTest extends TestCase
{
    /**
     * @test
     */
    public function filterValidReferencesRemovesIrrelevantTables()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $subject = new RecordService($persistenceService->reveal());

        $references = [
            ['uid' => 7],
            ['uid' => 14],
            ['uid' => 21],
        ];

        $persistenceService->getSysFileReferenceRecord(7)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 42]);
        $persistenceService->getRecord('tt_content', 42)->willReturn(['l18n_parent' => 21]);

        $persistenceService->getSysFileReferenceRecord(14)->willReturn(['tablenames' => 'pages', 'uid_foreign' => 42]);
        $persistenceService->getSysFileReferenceRecord(21)->willReturn(['tablenames' => 'sys_file', 'uid_foreign' => 42]);

        $result = $subject->filterValidReferences($references);
        $expected = [7];
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function filterValidReferencesRemovesTtContentItemsInFreeMode()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $subject = new RecordService($persistenceService->reveal());

        $references = [
            ['uid' => 7],
            ['uid' => 14],
        ];

        $persistenceService->getSysFileReferenceRecord(7)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 42]);
        $persistenceService->getRecord('tt_content', 42)->willReturn(['l18n_parent' => 21]);

        $persistenceService->getSysFileReferenceRecord(14)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 43]);
        $persistenceService->getRecord('tt_content', 43)->willReturn(['l18n_parent' => 0]);

        $result = $subject->filterValidReferences($references);
        $expected = [7];
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsTrueForTableContainingFalFields()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $subject = new RecordService($persistenceService->reveal());

        $GLOBALS['TCA']['foo']['columns']['bar']['config']['foreign_table'] = 'sys_file_reference';

        $this->assertTrue($subject->isFalConsumingTable('foo'));
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsFalseForTableNotContainingFalFields()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $subject = new RecordService($persistenceService->reveal());

        $GLOBALS['TCA']['foo']['columns']['bar']['config']['foreign_table'] = 'tt_content';

        $this->assertFalse($subject->isFalConsumingTable('foo'));
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsFalseForIrrelevantTable()
    {
        $persistenceService = $this->prophesize(PersistenceService::class);
        $subject = new RecordService($persistenceService->reveal());

        $this->assertFalse($subject->isFalConsumingTable('sys_file'));
    }
}
