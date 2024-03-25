<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\FileVariants\Tests\Functional\DataHandling;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;

final class PermissiveActionService extends ActionService
{
    protected function createDataHandler(): DataHandler
    {
        $dataHandler = parent::createDataHandler();
        $dataHandler->bypassAccessCheckForRecords = true;

        return $dataHandler;
    }
}
