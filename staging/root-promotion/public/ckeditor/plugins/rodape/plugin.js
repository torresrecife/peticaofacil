(function(){
	CKEDITOR.plugins.add( 'rodape',
	{
		init: function( editor )
		{
			editor.addCommand( 'insRod',
				{
					exec : function( editor )
					{    
						editor.insertHtml( '<p style="text-align: center;">___________________________________________________________________________</p><p style="text-align: center;"><span style="font-size:10px;">Rua Djalma Farias, 159, Torre&atilde;o - Recife - PE, CEP:52.030-195<br />Fone: 55 (81) 3222.2159<br />contato@brunovanderlei.adv.br<br />	www.brunovanderlei.adv.br</span></p>' );
					}
				});
			editor.ui.addButton( 'rodape',
			{
				label: 'Inserir Rodapé',
				command: 'insRod',
				icon: this.path + 'rod.gif'
			} );
		}
	} );
})();
