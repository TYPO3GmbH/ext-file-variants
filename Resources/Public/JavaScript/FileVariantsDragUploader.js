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

/**
 * Module: TYPO3/CMS/FileVariants/FileVariantsDragUploader
 *
 */
define(['jquery',
    'TYPO3/CMS/Backend/Notification',
    'TYPO3/CMS/Backend/FormEngine'
], function ($, Notification) {
  var percentagePerFile = 1;


    /*
     * part 1: a generic jQuery plugin "$.fileVariantsDragUploader"
     */

    // register the constructor
    /**
     *
     * @param {HTMLElement} element
     * @constructor
     * @exports TYPO3/CMS/FileVariants/FileVariantsDragUploader
     */
    var FileVariantsDragUploaderPlugin = function (element) {
        var me = this;
        me.$body = $('.t3js-filevariants-drag-uploader');
        me.$element = $(element);
        me.$trigger = $(me.$element.data('dropzone-trigger'));
        me.$dropzone = $('<div />').addClass('dropzone').appendTo(me.$body);
        me.dropZoneInsertBefore = false;
        me.$dropzoneMask = $('<div />').addClass('dropzone-mask').appendTo(me.$dropzone);
        me.$fileInput = $('<input type="file" name="files[]" />').addClass('upload-file-picker').appendTo(me.$body);
        me.filesExtensionsAllowed = me.$element.data('file-allowed');
        me.fileDenyPattern = me.$element.data('file-deny-pattern') ? new RegExp(me.$element.data('file-deny-pattern'), 'i') : false;
        me.maxFileSize = parseInt(me.$element.data('max-file-size'));
        me.target = me.$element.data('target-folder');
        me.ajaxHandlingUrl = me.$element.data('handling-url');

        me.browserCapabilities = {
            fileReader: typeof FileReader !== 'undefined',
            DnD: 'draggable' in document.createElement('span'),
            FormData: !!window.FormData,
            Progress: "upload" in new XMLHttpRequest
        };

        /**
         *
         * @param {Event} event
         */
        me.hideDropzone = function (event) {
            event.stopPropagation();
            event.preventDefault();
            me.$dropzone.hide();
        };

        /**
         *
         * @param {Event} event
         * @returns {Boolean}
         */
        me.dragFileIntoDocument = function (event) {
            event.stopPropagation();
            event.preventDefault();
            me.$body.addClass('drop-in-progress');
            return false;
        };

        /**
         *
         * @param {Event} event
         * @returns {Boolean}
         */
        me.dragAborted = function (event) {
            event.stopPropagation();
            event.preventDefault();
            me.$body.removeClass('drop-in-progress');
            return false;
        };

        /**
         *
         * @param {Event} event
         * @returns {Boolean}
         */
        me.ignoreDrop = function (event) {
            // stops the browser from redirecting.
            event.stopPropagation();
            event.preventDefault();
            me.dragAborted(event);
            return false;
        };

        /**
         *
         * @param {Event} event
         */
        me.handleDrop = function (event) {
            me.ignoreDrop(event);
            me.processFiles(event.originalEvent.dataTransfer.files);
            me.$dropzone.removeClass('drop-status-ok');
        };

        /**
         *
         * @param {Array} files
         */
        me.processFiles = function (files) {
            me.queueLength = files.length;

            new FileVariantsFileQueueItem(me, files[0], 'rename', me.ajaxHandlingUrl);
            me.$fileInput.val('');
        };

        /**
         *
         * @param {Event} event
         */
        me.fileInDropzone = function (event) {
            me.$dropzone.addClass('drop-status-ok');
        };

        /**
         *
         * @param {Event} event
         */
        me.fileOutOfDropzone = function (event) {
            me.$dropzone.removeClass('drop-status-ok');
        };

        if (me.browserCapabilities.DnD) {
            me.$body.on('dragover', me.dragFileIntoDocument);
            me.$body.on('dragend', me.dragAborted);
            me.$body.on('drop', me.ignoreDrop);

            me.$dropzone.on('dragenter', me.fileInDropzone);
            me.$dropzoneMask.on('dragenter', me.fileInDropzone);
            me.$dropzoneMask.on('dragleave', me.fileOutOfDropzone);
            me.$dropzoneMask.on('drop', me.handleDrop);

            me.$dropzone.prepend(
                '<div class="dropzone-hint">' +
                '<div class="dropzone-hint-media">' +
                '<div class="dropzone-hint-icon"></div>' +
                '</div>' +
                '<div class="dropzone-hint-body">' +
                '<h3 class="dropzone-hint-title">Drag & Drop to upload file variant</h3>' +
                '<p class="dropzone-hint-message">Drop a file here, or <u>click, browse & choose file</u></p>' +
                '</div>' +
                '</div>').click(function () {
                me.$fileInput.click()
            });
            me.$trigger = $(me.$element.data('dropzone-trigger'));

            me.$fileInput.on('change', function () {
                me.processFiles(this.files);
            });
        }

    };

    var FileVariantsFileQueueItem = function (fileVariantsDragUploader, file, override, handlingUrl) {
        var me = this;
        me.fileVariantsDragUploader = fileVariantsDragUploader;
        me.file = file;
        me.override = override;

        me.updateMessage = function (message) {
            Notification.error('Error', message, 0);
        };

        me.uploadStart = function () {
            me.fileVariantsDragUploader.$trigger.trigger('uploadStart', [me]);
        };

        me.uploadError = function (response) {
            me.updateMessage(TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, me.file.name));
            var error = $(response.responseText);
            if (error.is('t3err')) {
                me.$progressPercentage.text(error.text());
            } else {
                me.$progressPercentage.text('(' + response.statusText + ')');
            }
            me.$row.addClass('error');
            me.fileVariantsDragUploader.$trigger.trigger('uploadError', [me, response]);
        };



        me.uploadSuccess = function (data) {
            if (data.upload) {
                FileVariantsDragUploader.processFileVariantUpload(data.upload[0], handlingUrl);
            }
        };


        me.checkAllowedExtensions = function () {
            if (!me.fileVariantsDragUploader.filesExtensionsAllowed) {
                return true;
            }
            var extension = me.file.name.split('.').pop();
            var allowed = me.fileVariantsDragUploader.filesExtensionsAllowed.split(',');
            if ($.inArray(extension.toLowerCase(), allowed) !== -1) {
                return true;
            }
            return false;
        };


        // check file size
        if (me.fileVariantsDragUploader.maxFileSize > 0 && me.file.size > me.fileVariantsDragUploader.maxFileSize) {
            me.updateMessage(TYPO3.lang['file_upload.maxFileSizeExceeded']
                .replace(/\{0\}/g, me.file.name)
                .replace(/\{1\}/g, FileVariantsDragUploader.fileSizeAsString(me.fileVariantsDragUploader.maxFileSize)));
            me.$row.addClass('error');

            // check filename/extension against deny pattern
        } else if (me.fileVariantsDragUploader.fileDenyPattern && me.file.name.match(me.fileVariantsDragUploader.fileDenyPattern)) {
            me.updateMessage(TYPO3.lang['file_upload.fileNotAllowed'].replace(/\{0\}/g, me.file.name));
            me.$row.addClass('error');

        } else if (!me.checkAllowedExtensions()) {
            me.updateMessage(TYPO3.lang['file_upload.fileExtensionExpected']
                .replace(/\{0\}/g, me.fileVariantsDragUploader.filesExtensionsAllowed)
            );
            me.$row.addClass('error');
        } else {

            var formData = new FormData();
            formData.append('data[upload][1][target]', me.fileVariantsDragUploader.target);
            formData.append('data[upload][1][data]', '1');
            formData.append('overwriteExistingFiles', me.override);
            formData.append('redirect', '');
            formData.append('upload_1', me.file);

            var s = $.extend(true, {}, $.ajaxSettings, {
                url: TYPO3.settings.ajaxUrls['file_process'],
                contentType: false,
                processData: false,
                data: formData,
                cache: false,
                type: 'POST',
                success: me.uploadSuccess,
                error: me.uploadError
            });

            s.xhr = function () {
                return $.ajaxSettings.xhr();
            };

            // start upload
            me.upload = $.ajax(s);
        }
    };

    /**
     * part 2: The main module of this file
     * - initialize the FileVariantsDragUploader module and register
     * the jQuery plugin in the jQuery global object
     * when initializing the FileVariantsDragUploader module
     */
    var FileVariantsDragUploader = {};

    FileVariantsDragUploader.options = {};

    FileVariantsDragUploader.fileSizeAsString = function (size) {
        var string = '',
            sizeKB = size / 1024;

        if (parseInt(sizeKB) > 1024) {
            var sizeMB = sizeKB / 1024;
            string = sizeMB.toFixed(1) + ' MB';
        } else {
            string = sizeKB.toFixed(1) + ' KB';
        }
        return string;
    };

    FileVariantsDragUploader.processFileVariantUpload = function (file, url) {
        var ajaxurl = url + '&file=' + encodeURIComponent(file.uid);
        $('#t3js-fileinfo').load(ajaxurl, function() {
            $('.t3js-filevariants-drag-uploader').fileVariantsDragUploader();
        });
        $.ajax({
            url: TYPO3.settings.ajaxUrls['flashmessages_render'],
            cache: false,
            success: function(data) {
                $.each(data, function(index, flashMessage) {
                    Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity);
                });
            }
        });
    };

    FileVariantsDragUploader.initialize = function () {
        var me = this;

        // register the jQuery plugin "FileVariantsDragUploaderPlugin"
        $.fn.fileVariantsDragUploader = function (option) {
            return this.each(function () {
                var $this = $(this),
                    data = $this.data('FileVariantsDragUploaderPlugin');
                if (!data) {
                    $this.data('FileVariantsDragUploaderPlugin', (data = new FileVariantsDragUploaderPlugin(this)));
                }
                if (typeof option === 'string') {
                    data[option]();
                }
            });
        };

         $(function () {
             $('.t3js-filevariants-drag-uploader').fileVariantsDragUploader();
         });
    };


    /**
     * part 3: initialize the RequireJS module, require possible post-initialize hooks,
     * and return the main object
     */
    var initialize = function () {
        FileVariantsDragUploader.initialize();

        // load required modules to hook in the post initialize function
        if (
            'undefined' !== typeof TYPO3.settings
            && 'undefined' !== typeof TYPO3.settings.RequireJS
            && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules
            && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FileVariantsDragUploader']
        ) {
            $.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/FileVariantsDragUploader'], function (pos, moduleName) {
                require([moduleName]);
            });
        }

        // return the object in the global space
        return FileVariantsDragUploader;
    };

    // call the main initialize function and execute the hooks
    return initialize();

});
