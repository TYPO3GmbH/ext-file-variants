<?php
/**
 * Created by PhpStorm.
 * User: maddy
 * Date: 3/7/17
 * Time: 5:49 PM
 */

namespace T3G\AgencyPack\FileVariants\Tests\Unit\Service;


use T3G\AgencyPack\FileVariants\Service\FileHandlingService;
use TYPO3\CMS\Core\Resource\File;


class FileHandlingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileHandlingService
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new FileHandlingService();
    }

    /**
     * @test
     */
    public function moveUploadedFileAndCreateFileObjectThrowsExceptionIfNoFileCanBeCreated()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1488297422);
        $this->subject->moveUploadedFileAndCreateFileObject('foo');
    }

    /**
     * @test
     */
    public function moveUploadedFileAndCreateFileObjectCreatesFileObjectFromGivenFilename()
    {
        $result = $this->subject->moveUploadedFileAndCreateFileObject('foo');
        $this->assertInstanceOf(File::class, $result);
    }

}
