(function($) {
    // Run a function when the page is fully loaded including graphics.
    $(window).on('load', function() {
	let actions = ['create', 'massDestroy'];

	actions.forEach(function (action) {
	    $('#'+action).click( function() { $.fn[action](); });
	});

	$('.clickable-row').click(function(event) {
	    if(!$(event.target).hasClass('form-check-input')) {
		$(this).addClass('active').siblings().removeClass('active');
		window.location = $(this).data('href');
	    }
	});
    });

    $.fn.setSelectedItems = function() {
	let ids = [];
	let inputs = '';

	$('.form-check-input:checkbox:checked').each(function () {
	    ids.push($(this).data('item-id'));
	});

	if (ids.length === 0) {
	    alert('no item selected');
	    return false;
	}

	for (let i = 0; i < ids.length; i++) {
	    inputs += '<input type="hidden" name="ids[]" value="'+ids[i]+'">';
	}
	
	$('#selectedItems').append(inputs);

	return true;
    }

    $.fn.create = function() {
	window.location.replace($('#listUrl').val()+'/create');
    }

    $.fn.massDestroy = function() {
	if ($.fn.setSelectedItems()) {
	    $('#selectedItems').submit();
	    //alert('destroy');
	}
    }

})(jQuery);
