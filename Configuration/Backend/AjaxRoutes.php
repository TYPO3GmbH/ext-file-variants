<?php

use T3G\AgencyPack\FileVariants\Controller\FileVariantsController;

return [
    'tx_filevariants_deleteFileVariant' => [
        'path' => '/file_variants/delete_filevariant',
        'target' => FileVariantsController::class . '::ajaxDeleteFileVariant'
    ],
    'tx_filevariants_replaceFileVariant' => [
        'path' => '/file_variants/replace_filevariant',
        'target' => FileVariantsController::class . '::ajaxReplaceFileVariant'
    ],
    'tx_filevariants_uploadFileVariant' => [
        'path' => '/file_variants/upload_filevariant',
        'target' => FileVariantsController::class . '::ajaxUploadFileVariant'
    ],
];