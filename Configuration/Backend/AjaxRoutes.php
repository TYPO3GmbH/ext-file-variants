<?php

/*
 * This file is part of the package t3g/file_variants.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use T3G\AgencyPack\FileVariants\Controller\FileVariantsController;

return [
    'tx_filevariants_deleteFileVariant' => [
        'path' => '/file_variants/delete_filevariant',
        'target' => FileVariantsController::class . '::ajaxResetFileVariant'
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
