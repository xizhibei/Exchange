/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function(config) {
   config.filebrowserBrowseUrl = '/kcfinder/browse.php?type=files';
   config.filebrowserImageBrowseUrl = '/kcfinder/browse.php?type=images';
   config.filebrowserFlashBrowseUrl = '/kcfinder/browse.php?type=flash';
   config.filebrowserUploadUrl = '/kcfinder/upload.php?type=files';
   config.filebrowserImageUploadUrl = '/kcfinder/upload.php?type=images';
   config.filebrowserFlashUploadUrl = '/kcfinder/upload.php?type=flash';
   
   config.toolbar = 'Full';
   config.toolbar_Full = [
       ['Source','-','Preview','-','Templates'],
       ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
       ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
       ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
       '/',
       ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Link','Unlink','Anchor'],
       ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
       '/',
        ['Styles','Format','Font','FontSize'],
        ['TextColor','BGColor']
    ];
	config.protectedSource.push( /<\s*iframe[\s\S]*?>/gi ) ; // <iframe> tags
config.protectedSource.push( /<\s*frameset[\s\S]*?>/gi ) ; // <frameset> tags.
config.protectedSource.push( /<\s*frame[\s\S]*?>/gi ) ; // <frame> tags.
config.protectedSource.push( /<\s*script[\s\S]*?\/script\s*>/gi ) ; // <SCRIPT> tags.
config.protectedSource.push( /<%[\s\S]*?%>/g ) ; // ASP style server side code
config.protectedSource.push( /<\?[\s\S]*?\?>/g ) ; // PHP style server side code
config.protectedSource.push( /(<asp:[^\>]+>[\s|\S]*?<\/asp:[^\>]+>)|(<asp:[^\>]+\/>)/gi ) ;
};