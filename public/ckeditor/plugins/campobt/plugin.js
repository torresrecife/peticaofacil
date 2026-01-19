
var campos = str_retorno_ajax.split("|_|");

CKEDITOR.dialog.add( 'campobt', function( editor )
{
	var tenant_fields = []; //new Array();
	for(i=0;i<=campos.length;i++){
		strcampos = new String(campos[i]);
		var dados = strcampos.split("_|_");
		if(dados[1]!=undefined){
			tenant_fields[i]=[dados[0], dados[1]];
		}
	}
	return {
		title : 'Campos',
		minWidth : 300,
		minHeight : 100,
		contents :
		[
			{
				id : 'tab1',
				label : 'Tenants',
				elements :
				[
					{
						id : 'tenant_dropdown',
						type : 'select',
						label : 'Selecione o Campo.',
						'default':'',
						items: tenant_fields,
						onChange : function( api ) {
						  //this = CKEDITOR.ui.dialog.select
						}
					}
				]
			}
		],
		icon: this.path + 'tab.gif',

		onOk : function()
		{
			var dialog = this;
			var abbr = editor.document.createElement( 'rz_db' );
			abbr.setText( dialog.getValueOf( 'tab1', 'tenant_dropdown' ) );
			editor.insertElement( abbr );
		}
	};
} );

CKEDITOR.plugins.add( 'campobt',
{
	init : function( editor )
	{
		var command = editor.addCommand( 'campobt', new CKEDITOR.dialogCommand( 'campobt' ) );
		command.modes = { wysiwyg:1, source:1 };
		command.canUndo = false;

		editor.ui.addButton( 'CampoBT',
		{
			label : 'Inserir Campos',
			command : 'campobt',
			icon : this.path + 'campobt.png'
		});

		CKEDITOR.dialog.add( 'campobt', 'campobt' );
	}
});
