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
define(["require", "jquery", 'TYPO3/CMS/Backend/DragUploader'], function (require, $, DragUploader) {
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




            //
            // $(document).on('click', this.selector, function(e) {
            //     e.preventDefault();
            //     var url = $(this).data('url');
            //     var uploadForm = '<form action="#" method="post" enctype="multipart/form-data" name="file_variant_upload">' +
            //             '<label>File</label>' +
            //             '<input id="uploaded_file_name" name="variant" type="file" />' +
            //         '</form>';
            //
            //     var buttons = [
            //         {
            //             text: 'Cancel',
            //             btnClass: 'btn-default',
            //             trigger: function () {
            //                 Modal.currentModal.trigger('modal-dismiss');
            //             }
            //         },
            //         {
            //             text: 'Upload',
            //             active: true,
            //             btnClass: 'btn-info',
            //             trigger: function () {
            //                 Modal.currentModal.trigger('modal-dismiss');
            //                 var form = $(this).find('form');
            //                 console.log(form.attr('name'));
            //                 form.submit();
            //
            //             }
            //         }
            //     ];
            //     Modal.show('Upload file variant file', uploadForm, top.TYPO3.Severity.info, buttons);
            //
            //     var

            //     var content = 'foo';
            //     $('#t3js-fileinfo').html(content);
            // });
        };
        return FileVariants;
    }());
    return new FileVariants();
});
