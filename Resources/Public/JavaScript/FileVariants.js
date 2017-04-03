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
define([
    "require",
    "jquery",
    'TYPO3/CMS/Backend/Modal',
    'TYPO3/CMS/Backend/Severity',
	'TYPO3/CMS/FileVariants/FileVariantsDragUploader'
], function (require, $, Modal, Severity, FileVariantsDragUploader) {
    "use strict";
    /**
     * Module: TYPO3/CMS/FileVariants/FileVariants
     * contains all logic filevariants manipulation used in BE
     * @exports TYPO3/CMS/FileVariants/FileVariants
     */
    var FileVariants = (function () {
        /**
         * The constructor, set the class properties default values
         */
        function FileVariants() {
            this.selector = '.t3js-filevariant-trigger';
        }
        /**
         * Initialize the trigger for the given selector
         */
        FileVariants.prototype.initialize = function () {

            $(document).on('click', this.selector, function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                var modal = Modal.confirm('really?', 'do you want to remove the file variant?', Severity.info, [
                    {
                        text: TYPO3.lang['buttons.confirm.delete_record.no'] || 'Cancel',
                        active: true,
                        btnClass: 'btn-default',
                        name: 'no'
                    },
                    {
                        text: TYPO3.lang['buttons.confirm.delete_record.yes'] || 'Yes, reset to default',
                        btnClass: 'btn-warning',
                        name: 'yes'
                    }
                ]);
                modal.on('button.clicked', function(e) {
                    if (e.target.name === 'no') {
                        Modal.dismiss();
                    } else if (e.target.name === 'yes') {
                        $('#t3js-fileinfo').load(url);
                        Modal.dismiss();
                        // @todo reinitialize the drag uploader
						$('.t3js-filevariants-drag-uploader').fileVariantsDragUploader();
                    }
                });


            });
        };
        return FileVariants;
    }());
    return new FileVariants();
});
