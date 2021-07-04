
tinymce.init({
    selector: '#texteditor',
    toolbar: 'urldialog',
    height: 300,
    setup: function (editor) {
	editor.ui.registry.addButton('urldialog', {
	    icon: 'browse',
	    onAction: function () {
		editor.windowManager.openUrl({
		    title: 'File Manager',
		    url: 'https://codalia.dubya.net/starter/public/cms/documents',
		    buttons: [
			{
			    type: "cancel",
			    name: "cancel",
			    text: "Close Dialog"
			}
		    ],
		    height: 740,
		    width: 1240
		});
	    }
	});

	editor.addCommand("iframeCommand", function(ui, value) {
	    if (value.content_type.startsWith('image')) {
		editor.insertContent(
		    `<img src="${value.file_url}" alt="${value.file_name}">`
		);
	    }
	    else {
		editor.insertContent(
		    `<a target="_blank" href="${value.file_url}">${value.file_name}</a>`
		);
	    }
	});
    },

    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
});
