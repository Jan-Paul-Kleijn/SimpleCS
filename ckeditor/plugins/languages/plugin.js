/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( 'languages',
{
	requires : [ 'styles', 'button' ],

	init : function( editor )
	{
		var languages = editor.config.languages,
			items = {};

		for ( var i = 0 ; i < languages.length ; i++ )
		{
			var parts = languages[i].split( ':' );

			items[ 'language_' + parts[ 0 ] ] =
				{
					label : parts[ 1 ],
					group : 'languages',
					order : i,
					style : new CKEDITOR.style(
						{
							element : 'span',
							attributes : { 'lang' : parts[ 0 ], 'xml:lang' : parts[ 0 ] },
						}),
					onClick : function()
					{
						this.style.apply( editor.document );
					}
				};
		}

		editor.addMenuGroup( 'languages' );
		editor.addMenuItems( items );

		editor.ui.add( 'Languages', CKEDITOR.UI_MENUBUTTON,
			{
				label : 'Talen',
				title : 'Talen',
				modes : { wysiwyg:1 },
				className : 'cke_button_languages',
				icon : this.path + 'icon.png',
				onMenu : function()
				{
					var activeItems = {};

					for ( var prop in items )
					{
						if ( items.hasOwnProperty( prop ) )
							activeItems[ prop ] = CKEDITOR.TRISTATE_ON;
					}
					
					return  activeItems;
				}
			});
	}
});

CKEDITOR.config.languages = [ 'fr:French', 'es:Spanish', 'it:Italian' ];