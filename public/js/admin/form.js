(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      let actions = ['save', 'saveClose', 'cancel', 'delete'];

      actions.forEach(function (action) {
	  $('#'+action).click( function() { $.fn[action](); });
      });
  });

  $.fn.save = function() {
      $('#itemForm').submit();
  }

  $.fn.saveClose = function() {
      alert('saveClose');
  }

  $.fn.cancel = function() {
      alert('cancel');
  }

  $.fn.delete = function() {
      alert('delete');
  }

})(jQuery);

