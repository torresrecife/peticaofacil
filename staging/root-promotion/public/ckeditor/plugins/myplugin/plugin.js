(function(){
CKEDITOR.plugins.add( 'myplugin',
{
	init: function( editor )
	{
		editor.addCommand( 'mydialog',new CKEDITOR.dialogCommand( 'mydialog' ) );

		if ( editor.contextMenu )
		{
			editor.addMenuGroup( 'mygroup', 10 );
			editor.addMenuItem( 'My Dialog',
			{
				label : 'Open dialog',
				command : 'mydialog',
				group : 'mygroup'
			});
			editor.contextMenu.addListener( function( element )
			{
 				return { 'My Dialog' : CKEDITOR.TRISTATE_OFF };
			});
		}

		CKEDITOR.dialog.add( 'mydialog', function( api )
		{
			// CKEDITOR.dialog.definition
			var dialogDefinition =
			{
				title : 'Sample dialog',
				minWidth : 390,
				minHeight : 130,
				contents : [
					{
						id : 'tab1',
						label : 'Label',
						title : 'Title',
						expand : true,
						padding : 0,
						elements :
						[
							{
								type : 'html',
								html : '<p>This is some sample HTML content.</p>'
							},
							{
								type : 'textarea',
								id : 'textareaId',
								rows : 4,
								cols : 40
							}
						]
					}
				],
				buttons : [ CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton ],
				onOk : function() {
					// "this" is now a CKEDITOR.dialog object.
					// Accessing dialog elements:
					var textareaObj = this.getContentElement( 'tab1', 'textareaId' );
					alert( "You have entered: " + textareaObj.getValue() );
				}
			};

			return dialogDefinition;
		} );
	}
} );
})();