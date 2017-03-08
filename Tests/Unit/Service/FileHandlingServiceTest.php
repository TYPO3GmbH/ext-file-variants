<?php
/**
 * Created by PhpStorm.
 * User: maddy
 * Date: 3/7/17
 * Time: 5:49 PM
 */

namespace T3G\AgencyPack\FileVariants\Tests\Unit\Service;


use T3G\AgencyPack\FileVariants\Service\FileHandlingService;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;


class FileHandlingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileHandlingService
     */
    protected $subject;

    protected function setUp()
    {
        /** @var PersistenceService $persistenceService */
        $persistenceService = $this->prophesize(PersistenceService::class);
        $this->subject = new FileHandlingService($persistenceService->reveal());
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
     * @dataProvider getFileNames
     */
    public function calculateFullPathToUploadedFileReturnsSanitizedFilePath($filename)
    {
        $result = $this->subject->calculateFullPathToUploadedFile($filename);
        $expected = PATH_site . 'typo3temp/uploads/foo.pdf';
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getFileNames(): array
    {
        return [
            'one file given during upload' => [
                'foo.pdf',
            ],
            'two files given during upload' => [
                'bar.txt,foo.pdf',
            ],
        ];
    }

}
