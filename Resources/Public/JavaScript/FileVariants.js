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
define(["require", "jquery"], function (require, $) {
	"use strict";
	/**
	 * Module: TYPO3/CMS/FileVariants/FileVariants
	 * contains all logic for the color picker used in FormEngine
	 * @exports TYPO3/CMS/FileVariants/FileVariants
	 */
	var FileVariants = (function () {
		/**
		 * The constructor, set the class properties default values
		 */
		function FileVariants() {
			this.selector = '.t3js-delete-filevariant-trigger';
		}
		/**
		 * Initialize the color picker for the given selector
		 */
		FileVariants.prototype.initialize = function () {
			$(document).on('click', this.selector, function(e) {
				e.preventDefault();
				var url = $(this).data('url');
				$('#t3js-fileinfo').load(url);
			});
		};
		return FileVariants;
	}());
	return new FileVariants();
});
