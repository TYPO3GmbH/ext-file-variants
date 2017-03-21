<?php
declare(strict_types=1);
namespace T3G\AgencyPack\FileVariants\Controller;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\FileVariants\Service\FileRecordService;
use T3G\AgencyPack\FileVariants\Service\PersistenceService;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
  * Description
  */
class FileVariantsController {

    /**
     * @var FileRecordService
     */
    protected $fileRecordService;

    /**
     * FileVariantsController constructor.
     * @param FileRecordService $fileRecordService
     */
    public function __construct(FileRecordService $fileRecordService = null)
    {
        $this->fileRecordService = $fileRecordService;
        if ($this->fileRecordService === null) {

            $this->fileRecordService = GeneralUtility::makeInstance(FileRecordService::class, GeneralUtility::makeInstance(PersistenceService::class));
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxDeleteFileVariant(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uid = (int)$request->getQueryParams()['uid'];


        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        /** @var FormResultCompiler $formResultCompiler */
        $formDataCompilerInput = [
            'tableName' => 'sys_file_metadata',
            'vanillaUid' => $uid,
            'command' => 'edit',
        ];
        $formData = $formDataCompiler->compile($formDataCompilerInput);
        $formData['renderType'] = 'fileInfo';

        $this->fileRecordService->replaceVariantWithDefaultFile((int)$formData['databaseRow']['file'][0]);

        $formResult = $nodeFactory->create($formData)->render();
        $response->getBody()->write($formResult['html']);
        return $response;
    }


}
