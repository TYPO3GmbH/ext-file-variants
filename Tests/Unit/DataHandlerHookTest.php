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
use Prophecy\Prophecy\ObjectProphecy;
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
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

    protected function setUp()
    {
        $this->fileRecordService = $this->prophesize(FileRecordService::class);
        $this->referenceRecordService = $this->prophesize(ReferenceRecordService::class);
        $this->subject = new DataHandlerHook();
    }

    /**
     * @test
     */
    public function uponSysFileMetadataLocalisationDependingSysFileRecordGetsCopiedAndRelated()
    {
        $this->subject->processCmdmap_postProcess('localize', 'sys_file_metadata', 42, 1, $this->prophesize(DataHandler::class)->reveal(), [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal());
        $this->fileRecordService->copySysFileRecord()->shouldBeCalled();
        $this->referenceRecordService->updateReferences()->shouldBeCalled();
    }

    /**
     * @test
     */
    public function cmdMapHookDoesNotReactOnUnrelatedTable()
    {
        $this->subject->processCmdmap_postProcess('localize', 'tt_content', 42, 1, $this->prophesize(DataHandler::class)->reveal(), [], [], $this->fileRecordService->reveal(), $this->referenceRecordService->reveal());
        $this->fileRecordService->copySysFileRecord()->shouldNotBeCalled();
        $this->referenceRecordService->updateReferences()->shouldNotBeCalled();
    }

    /**
     * @test
     */
    public function uponSysFileMetadataUpdateWithFileVariantSetTheRelatedSysFileRecordGetsUpdated()
    {
        $this->subject->processDatamap_afterDatabaseOperations('update', 'sys_file_metadata', 42, ['file_variant' => 'foo'], $this->prophesize(DataHandler::class)->reveal(), $this->fileRecordService->reveal(), $this->referenceRecordService->reveal());
        $this->fileRecordService->updateSysFileRecord()->shouldBeCalled();
    }

}