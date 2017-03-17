<?php

use T3G\AgencyPack\FileVariants\Controller\FileVariantsController;

return [
    'tx_filevariants_deleteFileVariant' => [
        'path' => '/file_variants/delete_filevariant',
        'target' => FileVariantsController::class . '::ajaxDeleteFileVariant'
    ],
];
