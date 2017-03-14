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
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\RecordService;

class RecordServiceTest extends TestCase
{

    /**
     * @var RecordService
     */
    protected $subject;

    /**
     * @var PersistenceService|ObjectProphecy
     */
    protected $persistenceService;

    protected function setUp()
    {
        $this->persistenceService = $this->prophesize(PersistenceService::class);
        $this->subject = new RecordService($this->persistenceService->reveal());
    }

    /**
     * @test
     */
    public function filterValidReferencesRemovesIrrelevantTables()
    {

        $references = [
            ['uid' => 7],
            ['uid' => 14],
            ['uid' => 21],
        ];

        $this->persistenceService->getSysFileReferenceRecord(7)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 42]);
        $this->persistenceService->getRecord('tt_content', 42)->willReturn(['l18n_parent' => 21]);

        $this->persistenceService->getSysFileReferenceRecord(14)->willReturn(['tablenames' => 'pages', 'uid_foreign' => 42]);
        $this->persistenceService->getSysFileReferenceRecord(21)->willReturn(['tablenames' => 'sys_file', 'uid_foreign' => 42]);

        $result = $this->subject->filterValidReferences($references);
        $expected = [7];
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function filterValidReferencesRemovesTtContentItemsInConnectedMode()
    {
        $references = [
            ['uid' => 7],
            ['uid' => 14],
        ];

        $this->persistenceService->getSysFileReferenceRecord(7)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 42]);
        $this->persistenceService->getRecord('tt_content', 42)->willReturn(['l18n_parent' => 21]);

        $this->persistenceService->getSysFileReferenceRecord(14)->willReturn(['tablenames' => 'tt_content', 'uid_foreign' => 43]);
        $this->persistenceService->getRecord('tt_content', 43)->willReturn(['l18n_parent' => 0]);

        $result = $this->subject->filterValidReferences($references);
        $expected = [7];
        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsTrueForExistingFalFieldInTable()
    {
        $GLOBALS['TCA']['foo']['columns']['bar']['config']['foreign_table'] = 'sys_file_reference';
        $this->assertTrue($this->subject->isFalConsumingTable('foo'));
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsFalseForNoExistingFalFieldInTable()
    {
        $GLOBALS['TCA']['foo']['columns']['bar']['config']['foreign_table'] = 'tt_content';
        $this->assertFalse($this->subject->isFalConsumingTable('foo'));
    }

    /**
     * @test
     */
    public function isFalConsumingTableReturnsFalseForIrrelevantTable()
    {
        $this->assertFalse($this->subject->isFalConsumingTable('sys_file'));
    }
}
