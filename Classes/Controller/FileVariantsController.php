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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxDeleteFileVariant(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uid = (int)$request->getQueryParams()['uid'];
        /** @var PersistenceService $persistenceService */
        $persistenceService = GeneralUtility::makeInstance(PersistenceService::class);

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

        // replace the variant with the a copy of the original file
        $file = (int)$formData['databaseRow']['file'][0];
        $fileRecord = $persistenceService->getRecord('sys_file', $file);
        $defaultFileObject = ResourceFactory::getInstance()->getFileObject((int)$fileRecord['l10n_parent']);
        $copy = $persistenceService->copyFileObject($defaultFileObject, $defaultFileObject->getParentFolder());
        // this record will be stale after the replace, remove it right away
        $SysFileRecordToBeDeleted = $copy->getUid();
        $path = PATH_site . $copy->getPublicUrl();

        $persistenceService->replaceFile($file, $defaultFileObject->getName(), $path);

        $persistenceService->deleteRecord('sys_file', $SysFileRecordToBeDeleted);

        $formResult = $nodeFactory->create($formData)->render();
        $response->getBody()->write($formResult['html']);
        return $response;
    }
}
