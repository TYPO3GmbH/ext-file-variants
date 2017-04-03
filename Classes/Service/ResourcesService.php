<?php
declare(strict_types=1);
namespace T3G\AgencyPack\FileVariants\Service;

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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
  * Resources related helper methods
  */
class ResourcesService {

    public function retrieveStorageObject(int $uid): ResourceStorage
    {
        if ($uid === 0) {
            $storage = ResourceFactory::getInstance()->getDefaultStorage();
        } else {
            $storage = ResourceFactory::getInstance()->getStorageObject($uid);
        }
        return $storage;
    }

}
