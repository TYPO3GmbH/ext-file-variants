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

import $ from "jquery";
import Notification from"@typo3/backend/notification.js";
import FormEngine from"@typo3/backend/form-engine.js";
import FileVariantsFileQueueItem from "@t3g/file_variants/FileVariantsFileQueueItem.js";
import FileVariantsDragUploaderPlugin from "@t3g/file_variants/FileVariantsDragUploaderPlugin.js";

/**
 * Module: TYPO3/CMS/FileVariants/FileVariantsDragUploader
 *
 */
class FileVariantsDragUploader {

  /**
   * part 2: The main module of this file
   * - initialize the FileVariantsDragUploader module and register
   * the jQuery plugin in the jQuery global object
   * when initializing the FileVariantsDragUploader module
   */
  fileSizeAsString(size) {
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

  processFileVariantUpload(file, url) {
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
  }

  initialize() {

    var me = this;

    // register the jQuery plugin "FileVariantsDragUploaderPlugin"
    $.fn.fileVariantsDragUploader = function (option) {
      return this.each(function () {
        var $this = $(this),
          data = $this.data('FileVariantsDragUploaderPlugin');
        if (!data) {
          $this.data('FileVariantsDragUploaderPlugin', (data = FileVariantsDragUploaderPlugin.dragUpload(this)));
        }
        if (typeof option === 'string') {
          data[option]();
        }
      });
    };

    $(function () {
      $('.t3js-filevariants-drag-uploader').fileVariantsDragUploader();
    });
  }
}

export default new FileVariantsDragUploader();
