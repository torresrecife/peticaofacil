(function(){
	CKEDITOR.plugins.add( 'insertTab',
	{
		init: function( editor )
		{
			editor.addCommand( 'insTab',
				{
					exec : function( editor )
					{    
						editor.insertHtml( "<span style='margin-left: 40px'>&nbsp;</span>" );
					}
				});
			editor.ui.addButton( 'insertTab',
			{
				label: 'Inserir Tab',
				command: 'insTab',
				icon: this.path + 'tab.gif'
			} );
		}
	} );
})();
