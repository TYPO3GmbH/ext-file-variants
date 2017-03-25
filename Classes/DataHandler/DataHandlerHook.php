<?php
declare(strict_types=1);

namespace T3G\AgencyPack\FileVariants\DataHandler;
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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;

class DataHandlerHook
{
    /**
     * @var string
     */
    protected $uploadFolderPath = PATH_site . 'typo3temp/file_variants_uploads';

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorageInterface
     */
    protected $storage;

    /**
     * @var \TYPO3\CMS\Core\Resource\FolderInterface
     */
    protected $folder;

    /**
     * DataHandlerHook constructor.
     */
    public function __construct()
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants'])) {
            throw new \RuntimeException('No extension configuration found. Go to ExtensionManager and press the wheel symbol for ext:file_variants.', 1490476773);
        }
        if (!is_dir($this->uploadFolderPath)) {
            mkdir($this->uploadFolderPath, 2777, true);
        }
    }

    /**
     *
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        DataHandler $pObj
    )
    {
        if ($table !== 'sys_file_storage') {
            $this->prepareFileHandlingEnvironment();
        }
    }

    /**
     * make sure upload storage and folder are in place
     */
    protected function prepareFileHandlingEnvironment()
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['file_variants']);
        $storageUid = (int)$extensionConfiguration['variantsStorageUid'];
        $targetFolder = $extensionConfiguration['variantsFolder'];
        try {
            $this->storage = ResourceFactory::getInstance()->getStorageObject($storageUid);

            if (!$this->storage->hasFolder($targetFolder)) {
                $this->folder = $this->storage->createFolder($targetFolder);
            } else {
                $this->folder = $this->storage->getFolder($targetFolder);
            }
        } catch (\InvalidArgumentException $exception) {
            throw new \RuntimeException('storage with uid ' . $storageUid . ' is now available. Create it and check the given uid in extension configuration.', 1490480372);
        }
    }
}