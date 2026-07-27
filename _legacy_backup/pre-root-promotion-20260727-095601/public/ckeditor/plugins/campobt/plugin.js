
function getCampoBtRawValue() {
	var input = document.getElementById('str_retorno_ajax');
	if (input && typeof input.value === 'string' && input.value !== '') {
		return input.value;
	}
	if (typeof window.str_retorno_ajax === 'string' && window.str_retorno_ajax !== '') {
		return window.str_retorno_ajax;
	}
	if (typeof str_retorno_ajax === 'string' && str_retorno_ajax !== '') {
		return str_retorno_ajax;
	}
	return '';
}

function buildCampoBtItems() {
	var raw = getCampoBtRawValue();
	var campos = String(raw || '').split('|_|');
	var tenant_fields = [];
	var i;

	for (i = 0; i < campos.length; i++) {
		var strcampos = String(campos[i] || '');
		var dados = strcampos.split('_|_');
		if (dados.length > 1 && dados[1] !== undefined && dados[1] !== '') {
			tenant_fields.push([dados[0], dados[1]]);
		}
	}

	return tenant_fields;
}

CKEDITOR.dialog.add( 'campobt', function( editor )
{
	var tenant_fields = buildCampoBtItems();
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
