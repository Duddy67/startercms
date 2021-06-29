
tinymce.init({
    selector: '#texteditor',
    toolbar: 'urldialog',
    height: 300,
    setup: function (editor) {
	editor.ui.registry.addButton('urldialog', {
	    icon: 'code-sample',
	    onAction: function () {
		editor.windowManager.openUrl({
		    title: 'URL Dialog Demo',
		    url: 'https://codalia.dubya.net/starter/public/document',
		    buttons: [
			{
			    type: "custom",
			    name: "insert-and-close",
			    text: "Insert and Close",
			    primary: true,
			    align: "end"
			},
			{
			    type: "cancel",
			    name: "cancel",
			    text: "Close Dialog"
			}
		    ],
		    height: 640,
		    width: 640
		});
	    }
	});

	editor.addCommand("iframeCommand", function(ui, value) {
	    editor.insertContent(
		`<img src="${value.file_url}" alt=" ${ value.file_name }">`
	    );
	});
    },

    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
});
