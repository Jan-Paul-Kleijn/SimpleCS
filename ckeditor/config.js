/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config ) {
	 config.uiColor = '#eeeedd';
  config.defaultLanguage = 'nl';
  config.skin = 'kama';
  config.extraPlugins = 'abbr,scayt,languages,youtube,mp4player,artistry,pastefromword';
  config.contentsCss = ['/css/styles.css'];
  config.bodyClass = 'ckeditor_view';
  config.justifyClasses = [ 'alignLeft', 'alignCenter', 'alignRight', 'alignJustify' ];
  config.format_tags = 'h1;h2;h3;h4;h5;h6;pre;p';
  config.languages = [ 'de:Deutsch', 'en:English', 'es:Espana', 'fr:Francais', 'it:Italiano' ];
  config.disableObjectResizing = false;
  config.height = '300px';
  config.removeFormatTags = 'abbr,b,big,code,del,dfn,em,font,i,ins,kbd,q,samp,small,span,strike,strong,sub,sup,tt,u,var';
  config.fillEmptyBlocks = false;
  config.templates_replaceContent = false;
  config.youtube_width = '640';
  config.youtube_height = '480';
  config.youtube_related = true;
  config.youtube_older = false;
  config.youtube_privacy = false;
  config.allowedContent = false;
  config.disallowedContent = 'img[style]';
  config.extraAllowedContent = 'video[*]{*};source[*]{*}';
  config.pasteFromWordPromptCleanup = false;
  config.basicEntities = false;
  config.entities_processNumerical = true;
  config.entities_additional = 'lt,gt,amp,apos,quot'
  config.entities_latin = false;
  config.entities_greek = false;
//  config.entities_processNumerical == 'force';
  config.tabIndex = 1;
  config.colorButton_colors = '000,800000,8B4513,2F4F4F,008080,000080,4B0082,696969,B22222,A52A2A,DAA520,006400,0000CD,800080,808080,F00,FF8C00,FFD700,008000,0CF,06F,EE82EE,A9A9A9,FFA07A,FFA500,FFFF00,3C3,AFEEEE,ADD8E6,DDA0DD,D3D3D3,FFF0F5,FAEBD7,FFFFE0,F0FFF0,F0FFFF,F0F8FF,E6E6FA,FFF';
  config.protectedSource.push(/<\?[\s\S]*?\?>/g); // PHP Code
//  config.allowedContent = true;
//  config.autoParagraph = false;

// Toolbar layout
  config.toolbar = [
  	{ name: 'document', items: [ 'Source', 'ShowBlocks', 'Maximize', 'Print', 'Preview', 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', 'Undo', 'Redo', '-', 'Find', 'Replace', 'SelectAll', '-', 'SpellChecker', 'Scayt', 'RemoveFormat', 'Templates' ] },
  	'/',
  	{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
    { name: 'accessibility', items: [ 'Abbr', 'Languages', 'SpecialChar' ] },
    { name: 'linking', items: [ 'Link', 'Unlink', 'Anchor' ] },
    { name: 'embedButtons', items: [ 'Image', 'Flash', 'Youtube', 'Mp4Player', 'artistry' ] },
    { name: 'draw', items: [ 'CreateDiv', 'Table', 'HorizontalRule' ] },
  	'/',
    { name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
    { name: 'colors',      items : [ 'TextColor','BGColor' ] },
  	{ name: 'texttools', items: [ 'Bold', 'Italic', 'Underline', 'Strike','-','RemoveFormat' ] },
  ];
};
