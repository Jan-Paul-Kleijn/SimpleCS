CKEDITOR.plugins.add( 'abbr',
{
	init: function( editor )
	{
		var iconPath = this.path + 'images/icon.png';
 
		editor.addCommand( 'abbrDialog',new CKEDITOR.dialogCommand( 'abbrDialog' ) );
 
		editor.ui.addButton( 'Abbr',
		{
			label: 'Afkorting invoegen',
			command: 'abbrDialog',
			icon: iconPath
		} );
 
		if ( editor.contextMenu )
		{
			editor.addMenuGroup( 'myGroup' );
			editor.addMenuItem( 'abbrItem',
			{
				label : 'Afkorting wijzigen',
				icon : iconPath,
				command : 'abbrDialog',
				group : 'myGroup'
			});
			editor.contextMenu.addListener( function( element )
			{
				if ( element )
					element = element.getAscendant( 'abbr', true );
				if ( element && !element.isReadOnly() && !element.data( 'cke-realelement' ) )
 					return { abbrItem : CKEDITOR.TRISTATE_OFF };
				return null;
			});
		}
 
		CKEDITOR.dialog.add( 'abbrDialog', function( editor )
		{
			return {
				title : 'Afkorting eigenschappen',
				minWidth : 400,
				minHeight : 200,
				contents :
				[
					{
						id : 'tab1',
						label : 'Standaard',
						elements :
						[
							{
								type : 'text',
								id : 'abbr',
								label : 'Afkorting',
								validate : CKEDITOR.dialog.validate.notEmpty( "Het afkorting veld moet ingevuld worden" ),
								setup : function( element )
								{
									this.setValue( element.getText() );
								},
								commit : function( element )
								{
									element.setText( this.getValue() );
								}
							},
							{
								type : 'text',
								id : 'title',
								label : 'Uitleg',
								validate : CKEDITOR.dialog.validate.notEmpty( "Het uitleg veld moet ingevuld worden" ),
								setup : function( element )
								{
									this.setValue( element.getAttribute( "title" ) );
								},
								commit : function( element )
								{
									element.setAttribute( "title", this.getValue() );
								}
							}	 
						]
					},
					{
						id : 'tab2',
						label : 'Geavanceerd',
						elements :
						[
							{
								type : 'text',
								id : 'id',
								label : 'Id',
								setup : function( element )
								{
									this.setValue( element.getAttribute( "id" ) );
								},
								commit : function ( element )
								{
									var id = this.getValue();
									if ( id )
										element.setAttribute( 'id', id );
									else if ( !this.insertMode )
										element.removeAttribute( 'id' );
								}
							}
						]
					}
				],
				onShow : function()
				{
					var sel = editor.getSelection(),
						element = sel.getStartElement();
					if ( element )
						element = element.getAscendant( 'abbr', true );
 
					if ( !element || element.getName() != 'abbr' || element.data( 'cke-realelement' ) )
					{
						element = editor.document.createElement( 'abbr' );
						this.insertMode = true;
					}
					else
						this.insertMode = false;
 
					this.element = element;
 
					this.setupContent( this.element );
				},
				onOk : function()
				{
					var dialog = this,
						abbr = this.element;
 
					if ( this.insertMode )
						editor.insertElement( abbr );
					this.commitContent( abbr );
				}
			};
		} );
	}
} );