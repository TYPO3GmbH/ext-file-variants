import $ from "jquery";
import FileVariantsFileQueueItem from "@t3g/file_variants/FileVariantsFileQueueItem.js";
import FileVariantsDragUploader from "@t3g/file_variants/FileVariantsDragUploader.js";

class FileVariantsDragUploaderPlugin
{

  /**
   *
   * @param {HTMLElement} element
   * @constructor
   * @exports TYPO3/CMS/FileVariants/FileVariantsDragUploader
   */
  dragUpload (element) {
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

      FileVariantsFileQueueItem.performUpload(me, files[0], 'rename', me.ajaxHandlingUrl, me.maxFileSize, me.fileDenyPattern,
        me.filesExtensionsAllowed, me.target);

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
  }

}

export default new FileVariantsDragUploaderPlugin();
