/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

var campos = str_retorno_ajax.split("|_|");

var val_ajax = '';

CKEDITOR.dialog.add( 'smiley', function( editor ){
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
						label : 'Selecione o ítem do Campo:',
						'default':'',
						items: tenant_fields,
						onChange : function( api ) {
						  alert(element.getHtml());
						}
					},
					{
						type: 'radio',
						id: 'selectcampos',
						label: 'Selecione a forma do Campo:',
						items: [ [ 'Toda Minúscula', '0' ],[ 'Toda Maiúscula', '1' ],[ 'Primeira maiúscula', '2' ] ],
						style: 'color: green',
						'default': '0',
						onClick: function(){
							mucampos(this,this.getValue());
						}
					},
					{
						type: 'html',
						html: "<div id='id_selinp' style='display:none;width:94%;border:1px solid #ccc;padding:5px;margin:5px' ></div>"
					}
				]
			}
		],
		buttons : [ 
					{
						id:'cancel',
						type:'button',
						label: 'Novo Campo',
						'class':'cke_dialog_ui_button_cancel', 
						onClick:function(S){
							add_dado();
							var T=S.data.dialog;
							if(T.fire('cancel',{hide:true}).hide!==false)T.hide();
						}
					},
					CKEDITOR.dialog.okButton, 
					CKEDITOR.dialog.cancelButton 
				],
		onOk : function(){
			var textareaObj = this.getContentElement( 'tab1', 'tenant_dropdown' );
			editor.insertHtml( textareaObj.getValue());
		}
	};
	
});
