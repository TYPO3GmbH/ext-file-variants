import $ from "jquery";
import Notification from"@typo3/backend/notification.js";
import FormEngine from"@typo3/backend/form-engine.js";
import FileVariantsDragUploader from "@t3g/file_variants/FileVariantsDragUploader.js";

class FileVariantsFileQueueItem
{
  performUpload(fileVariantsDragUploader, file, override, handlingUrl, maxFileSize, fileDenyPattern,
                filesExtensionsAllowed, target) {

    console.log('performUpload');
    var me = this;
    me.fileVariantsDragUploader = fileVariantsDragUploader;
    me.file = file;
    me.override = override;
    me.filesExtensionsAllowed = filesExtensionsAllowed;

    console.log('performUpload: file name=' + this.file.name);

    me.updateMessage = function (message) {
      Notification.error('Error', message, 0);
    };

    me.uploadStart = function () {
      me.fileVariantsDragUploader.$trigger.trigger('uploadStart', [me]);
    }.bind(me);;

    me.uploadError = function (response) {
      console.log('uploadError file.name=' + me.file.name);
      me.updateMessage(TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, me.file.name));
      var error = $(response.responseText);
      if (error.is('t3err')) {
        console.log('uploadError: is error');
        //me.$progressPercentage.text(error.text());
      } else {
        console.log('uploadError: statusText=' + response.statusText);
        me.$progressPercentage.text('(' + response.statusText + ')');
      }
      // Cannot read properties of undefined (reading 'addClass')
      me.$row.addClass('error');
      me.fileVariantsDragUploader.$trigger.trigger('uploadError', [me, response]);
    }.bind(me);

    me.uploadSuccess = function (data) {
      console.log('uploadSuccess');
      if (data.upload) {
        FileVariantsDragUploader.processFileVariantUpload(data.upload[0], handlingUrl);
        //me.fileVariantsDragUploader.processFileVariantUpload(data.upload[0], handlingUrl);

        /*
        let file = data.upload[0];
        let url = handlingUrl;

        // temp. workaround (should call fileVariantsDragUploader.processFileVariantUpload instead)
        var ajaxurl = url + '&file=' + encodeURIComponent(file.uid);
        $('#t3js-fileinfo').load(ajaxurl, function () {
          $('.t3js-filevariants-drag-uploader').fileVariantsDragUploader();
        });
        $.ajax({
          url: TYPO3.settings.ajaxUrls['flashmessages_render'],
          cache: false,
          success: function (data) {
            $.each(data, function (index, flashMessage) {
              Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity);
            });
          }
        });

        // temp woraround end
        
         */


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
      console.log('performUpload: target=' + target);

      formData.append('data[upload][1][target]', target);
      formData.append('data[upload][1][data]', '1');
      formData.append('overwriteExistingFiles', this.override);
      formData.append('redirect', '');
      formData.append('upload_1', this.file);

      console.log('send ajax request');

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

  /*
  uploadError(response)
  {
    this.updateMessage(TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, this.file.name));
    var error = $(response.responseText);
    if (error.is('t3err')) {
      this.$progressPercentage.text(error.text());
    } else {
      this.$progressPercentage.text('(' + response.statusText + ')');
    }
    this.$row.addClass('error');
    this.fileVariantsDragUploader.$trigger.trigger('uploadError', [me, response]);
  }

  updateMessage(message)
  {
    Notification.error('Error', message, 0);
  }

  uploadStart()
  {
    this.fileVariantsDragUploader.$trigger.trigger('uploadStart', [me]);
  }

  uploadSuccess(data)
  {
    if (data.upload) {
      this.processFileVariantUpload(data.upload[0], handlingUrl);
    }
  }
  */


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
