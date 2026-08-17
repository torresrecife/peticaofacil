(function() {
	CKEDITOR.plugins.add('importword', {
		init: function(editor) {
			editor.addCommand('importWordDocument', {
				exec: function(editorInstance) {
					editorInstance.fire('wordImportRequested');
				}
			});

			editor.ui.addButton('ImportWord', {
				label: 'Importar arquivo Word',
				command: 'importWordDocument',
				icon: this.path + 'icons/importword.svg'
			});
		}
	});
})();
