(function($) {
    // Run a function when the page is fully loaded including graphics.
    $(window).on('load', function() {

	$('#cancel').click (function () {
	    $('#batch-window', parent.document).css('display', 'none');
	});

	$('#massUpdate').click (function () {
	    /*$('input[name="_method"]', parent.document).val('put');
	    let action = $('#selectedItems', parent.document).attr('action');
	    $('#selectedItems', parent.document).attr('action', action+'/batch');
	    $('#selectedItems', parent.document).submit();*/
	    //let iframe = $('iframe[name="batch"]');
	    //$('iframe[name="batch"]').find('form').submit();
	    $('#batchForm').submit();
	        /*$('iframe[name="batch"]').load(function() {
		    $(this).contents().find('form').submit(function() {
			return false;
		    });
		});*/
	});
    });

})(jQuery);
