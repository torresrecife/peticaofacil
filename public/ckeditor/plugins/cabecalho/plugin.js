(function(){
	CKEDITOR.plugins.add( 'cabecalho',
	{
		init: function( editor )
		{
			editor.addCommand( 'insCab',
				{
					exec : function( editor )
					{    
						editor.insertHtml( '<div style="text-align:right"><img alt="" src="img/userfiles/fabiotorres/images/cabecalho.jpg" style="width: 196px; height: 87px;" /></div>' );
					}
				});
			editor.ui.addButton( 'cabecalho',
			{
				label: 'Inserir Cabeçalho',
				command: 'insCab',
				icon: this.path + 'cab.gif'
			} );
		}
	} );
})();
