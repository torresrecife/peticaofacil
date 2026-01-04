(function(){
	CKEDITOR.plugins.add( 'assinatura',
	{
		init: function( editor )
		{
			editor.addCommand( 'insAss',
				{
					exec : function( editor )
					{    
						editor.insertHtml( '<p style="text-align: left;"><span style="margin-left: 40px"><span style="margin-left: 40px">&nbsp;</span></span><img alt="" src="/peticaofacil/img/userfiles/fabiotorres/images/assinatura.jpg" style="width: 288px; height: 140px;" /></p>' );
					}
				});
			editor.ui.addButton( 'assinatura',
			{
				label: 'Inserir Assinatura',
				command: 'insAss',
				icon: this.path + 'ass.gif'
			} );
		}
	} );
})();
