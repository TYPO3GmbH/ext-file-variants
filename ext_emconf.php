<?php
/************************************************************************
 * Extension Manager/Repository config file for ext "file_variants".
 ************************************************************************/
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Translatable files',
    'description' => 'Files can present their language variants and use them',
    'category' => 'extension',
    'constraints' => array(
        'depends' => array(
            'typo3' => '8.6.0-8.99.99'
        ),
        'conflicts' => array(),
    ),
    'autoload' => array(
        'psr-4' => array(
            'T3G\\AgencyPack\\FileVariants\\' => 'Classes',
        ),
    ),
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'typo3temp/uploads',
    'clearCacheOnLoad' => 1,
    'author' => 'Anja Leichsenring',
    'author_email' => 'anja.leichsenring@typo3.com',
    'author_company' => 'TYPO3 GmbH',
    'version' => '0.6.0',
);
