<?php
namespace T3G\AgencyPack\FileVariants\Tests\Unit;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
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

    protected function setUp()
    {
        $this->fileRecordService = $this->prophesize(FileRecordService::class);
        $this->subject = new DataHandlerHook();
        $this->subject->initializeServices($this->fileRecordService->reveal());
    }

    /**
     * @test
     */
    public function noMatchInHookConditionForNonRelatedActions()
    {
        $this->subject->processDatamap_afterDatabaseOperations('new', 'foo', 42, [], $this->prophesize(DataHandler::class)->reveal());
        $this->fileRecordService->relateFileVariantToSysFileMetadataRecord()->shouldNotBeCalled();
    }

    /**
     * @test
     */
    public function matchInHookConditionCallsHookAction()
    {
        $this->subject->processDatamap_afterDatabaseOperations('update', 'sys_file_metadata', 42, ['file_variant' => 'foo'], $this->prophesize(DataHandler::class)->reveal());
        $this->fileRecordService->relateFileVariantToSysFileMetadataRecord()->shouldNotBeCalled();
    }

}