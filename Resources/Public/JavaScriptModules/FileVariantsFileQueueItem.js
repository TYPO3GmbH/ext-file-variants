import $ from "jquery";
import Notification from"@typo3/backend/notification.js";
import FormEngine from"@typo3/backend/form-engine.js";
import FileVariantsDragUploader from "@t3g/file_variants/FileVariantsDragUploader.js";

class FileVariantsFileQueueItem
{
  performUpload(fileVariantsDragUploader, file, override, handlingUrl, maxFileSize, fileDenyPattern,
                filesExtensionsAllowed, target) {

    var me = this;
    me.fileVariantsDragUploader = fileVariantsDragUploader;
    me.file = file;
    me.override = override;
    me.filesExtensionsAllowed = filesExtensionsAllowed;

    me.updateMessage = function (message) {
      Notification.error('Error', message, 0);
    };

    me.uploadStart = function () {
      me.fileVariantsDragUploader.$trigger.trigger('uploadStart', [me]);
    }.bind(me);;

    me.uploadError = function (response) {
      me.updateMessage(TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, me.file.name));
      var error = $(response.responseText);
      if (error.is('t3err')) {
        me.$progressPercentage.text(error.text());
      } else {
        me.$progressPercentage.text('(' + response.statusText + ')');
      }
      // Cannot read properties of undefined (reading 'addClass')
      me.$row.addClass('error');
      me.fileVariantsDragUploader.$trigger.trigger('uploadError', [me, response]);
    }.bind(me);

    me.uploadSuccess = function (data) {
      if (data.upload) {
        FileVariantsDragUploader.processFileVariantUpload(data.upload[0], handlingUrl);
      }
    }.bind(me);


    // check file size
    if (maxFileSize > 0 && me.file.size > maxFileSize) {
      me.updateMessage(TYPO3.lang['file_upload.maxFileSizeExceeded']
        .replace(/\{0\}/g, me.file.name)
        .replace(/\{1\}/g, FileVariantsDragUploader.fileSizeAsString(maxFileSize)));
      me.$row.addClass('error');

      // check filename/extension against deny pattern
    } else if (fileDenyPattern && me.file.name.match(fileDenyPattern)) {
      me.updateMessage(TYPO3.lang['file_upload.fileNotAllowed'].replace(/\{0\}/g, me.file.name));
      me.$row.addClass('error');

    } else if (!this.checkAllowedExtensions()) {
      me.updateMessage(TYPO3.lang['file_upload.fileExtensionExpected']
        .replace(/\{0\}/g, filesExtensionsAllowed)
      );
      me.$row.addClass('error');
    } else {

      var formData = new FormData();

      formData.append('data[upload][1][target]', target);
      formData.append('data[upload][1][data]', '1');
      formData.append('overwriteExistingFiles', this.override);
      formData.append('redirect', '');
      formData.append('upload_1', this.file);

      var s = $.extend(true, {}, $.ajaxSettings, {
        url: TYPO3.settings.ajaxUrls['file_process'],
        contentType: false,
        processData: false,
        data: formData,
        cache: false,
        type: 'POST',
        success: this.uploadSuccess,
        error: this.uploadError
      });

      s.xhr = function () {
        return $.ajaxSettings.xhr();
      };

      // start upload
      this.upload = $.ajax(s);
    }
  }

  checkAllowedExtensions ()
  {
    if (!this.filesExtensionsAllowed) {
      return true;
    }
    var extension = this.file.nathis.split('.').pop();
    var allowed = this.filesExtensionsAllowed.split(',');
    if ($.inArray(extension.toLowerCase(), allowed) !== -1) {
      return true;
    }
    return false;
  }
}

export default new FileVariantsFileQueueItem();
