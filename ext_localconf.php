
<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied!');
}

// NEVER! use namespaces or use statements in this file!

call_user_func(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['file_variants'] = \T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['file_variants'] = \T3G\AgencyPack\FileVariants\DataHandler\DataHandlerHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1489747688] = [
        'nodeName' => 'fileInfo',
        'priority' => 30,
        'class' => \T3G\AgencyPack\FileVariants\FormEngine\FileInfoElement::class,
    ];

});
