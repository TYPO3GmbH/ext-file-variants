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
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use T3G\AgencyPack\FileVariants\Service\ReferenceRecordService;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Class DataHandlerHookTest
 * @package T3G\AgencyPack\FileVariants\Tests\Unit
 */
class DataHandlerHookTest extends TestCase
{
    /**
     * @var DataHandlerHook
     */
    protected $subject;

    /**
     * @var FileRecordService|ObjectProphecy
     */
    protected $fileRecordService;

    /**
     * @var ReferenceRecordService|ObjectProphecy
     */
    protected $referenceRecordService;

    /**
     * @var PersistenceService|ObjectProphecy
     */
    protected $persistenceService;

    protected function setUp()
    {
        $this->fileRecordService = $this->prophesize(FileRecordService::class);
        $this->referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $this->persistenceService = $this->prophesize(PersistenceService::class);
        $this->subject = new DataHandlerHook();
    }

    /**
     * @test
     */
    public function uponSysFileMetadataLocalisationDependingSysFileRecordGetsCopiedAndRelated()
    {
        $this->persistenceService->getSysFileMetaDataRecord(42, 1)->willReturn(['file' => 28, 'uid' => 11]);
        $this->fileRecordService->copySysFileRecord(28,1)->willReturn(30);
        $this->fileRecordService->updateSysFileMetadata(11, 30)->shouldBeCalled();
        $this->subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 42, 1, $this->prophesize(DataHandler::class)->reveal(), [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal(), $this->persistenceService->reveal());
        $this->referenceRecordService->updateReferences(28, 30, 1)->shouldBeCalled();
    }

    /**
     * @test
     */
    public function hookThrowsExceptionIfNoValidIdCanBeFound()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1489332067);
        $pObj = $this->prophesize(DataHandler::class);
        $this->subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 'NEW123456', 1, $pObj->reveal(), [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal(), $this->persistenceService->reveal());
    }

    /**
     * @test
     */
    public function uponSysFileMetadataLocalisationRealUidIsUsed()
    {
        $this->persistenceService->getSysFileMetaDataRecord(21, 1)->willReturn(['file' => 28, 'uid' => 11]);
        $this->fileRecordService->copySysFileRecord(Argument::cetera())->shouldBeCalled();
        $this->fileRecordService->updateSysFileMetadata(Argument::cetera())->shouldBeCalled();
        /** @var DataHandler $pObj */
        $pObj = $this->prophesize(DataHandler::class)->reveal();
        $pObj->substNEWwithIDs['NEW123456'] = 21;
        $this->subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 'NEW123456', 1, $pObj, [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal(), $this->persistenceService->reveal());
        $this->referenceRecordService->updateReferences(Argument::cetera())->shouldBeCalled();
    }

    /**
     * @test
     */
    public function cmdMapHookDoesNotReactOnUnrelatedTable()
    {
        $this->subject->processCmdmap_postProcess('localize', 'tt_content', 42, 1, $this->prophesize(DataHandler::class)->reveal(), [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal(), $this->persistenceService->reveal());
        $this->fileRecordService->copySysFileRecord()->shouldNotBeCalled();
        $this->referenceRecordService->updateReferences()->shouldNotBeCalled();
    }

    /**
     * @test
     */
    public function uponSysFileMetadataUpdateWithFileVariantSetTheRelatedSysFileRecordGetsUpdated()
    {
        $this->subject->processDatamap_afterDatabaseOperations('update', 'sys_file_metadata', 42, ['file_variant' => 'foo'], $this->prophesize(DataHandler::class)->reveal(), $this->fileRecordService->reveal(), $this->referenceRecordService->reveal(), $this->persistenceService->reveal());
        $this->fileRecordService->updateSysFileRecord()->shouldBeCalled();
    }

}