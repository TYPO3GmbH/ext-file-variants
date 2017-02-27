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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
  * Description
  */
class DataHandlerHook {

    /**
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param DataHandler $pObj
     */
    public function processDatamap_postProcessFieldArray( string $status, string $table, $id, array $fieldArray, DataHandler &$pObj ) {

        //DebuggerUtility::var_dump($table, $status, 8, true);
//        if ($table === 'sys_file_metadata' && $status === 'update') {
//            DebuggerUtility::var_dump($fieldArray, 'fieldArray');
//        }
    }

    public function processCmdmap_postProcess(string $command, string $table, $id, $value, DataHandler &$pObj, $pasteUpdate, array $pasteDatamap)
    {
        //DebuggerUtility::var_dump($table, $command, 8, true);

    }

}
