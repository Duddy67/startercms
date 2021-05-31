(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      let actions = ['save', 'saveClose', 'cancel', 'destroy'];

      actions.forEach(function (action) {
	  $('#'+action).click( function() { $.fn[action](); });
      });
  });

  $.fn.save = function() {
      $('#itemForm').submit();
  }

  $.fn.saveClose = function() {
      $('input[name="_close"]').val(1);
      $('#itemForm').submit();
  }

  $.fn.cancel = function() {
      window.location.replace($('#listUrl').val());
  }

  $.fn.destroy = function() {
      $('#deleteItemForm').submit();
      /*alert($('#listUrl').val()+'/2');
      $.ajax({
	  type: 'DELETE',
	  url: $('#listUrl').val()+'/2',
	  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
	});*/
  }

  if (jQuery.fn.select2) {
      $('.select2').select2();
  }

})(jQuery);

