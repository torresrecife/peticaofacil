/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {
	config.skin = 'office2013';
	config.allowedContent = true;
	config.stylesSet = 'peticao_juridica';
	config.format_tags = 'p;h2;h3;pre';
	config.removePlugins = 'elementspath';
	config.resize_enabled = false;
	config.autoGrow_onStartup = true;
	config.autoGrow_bottomSpace = 48;
	config.autoGrow_minHeight = 920;
	config.startupOutlineBlocks = false;
	config.fillEmptyBlocks = false;
	config.enterMode = CKEDITOR.ENTER_P;
	config.shiftEnterMode = CKEDITOR.ENTER_BR;
	config.font_defaultLabel = 'Arial';
	config.font_names =
		'Arial/Arial, Helvetica, sans-serif;' +
		'Calibri/Calibri, Arial, sans-serif;' +
		'Times New Roman/Times New Roman, Times, serif;' +
		'Georgia/Georgia, serif';
	config.fontSize_defaultLabel = '12';
	config.fontSize_sizes = '10/10px;12/12px;14/14px;16/16px;18/18px;20/20px';
	config.toolbar = [
		{ name: 'clipboard', items: [ 'Undo', 'Redo', '-', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'SelectAll' ] },
		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat', 'CopyFormatting' ] },
		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
		'/',
		{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
		{ name: 'colors', items: [ 'TextColor', 'BGColor' ] },
		{ name: 'insert', items: [ 'Table', 'HorizontalRule', 'PageBreak', 'SpecialChar', 'Smiley', 'Image', 'Link', 'Unlink' ] },
		{ name: 'document', items: [ 'ShowBlocks', 'Maximize', 'Source', 'Preview', 'Print' ] }
	];
};
