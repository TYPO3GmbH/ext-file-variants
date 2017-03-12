<?php
declare(strict_types = 1);
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

/**
 * Description
 */
class DataHandlerHook
{
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
    ) {

    }

    /**
     * @param string $command
     * @param string $table
     * @param string|int $id recordUid
     * @param mixed $value Command Value
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $id,
        $value
    ) {
    }

}
