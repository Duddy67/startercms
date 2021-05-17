(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      let actions = ['create', 'delete'];

      actions.forEach(function (action) {
	  $('#'+action).click( function() { $.fn[action](); });
      });

      $('.clickable-row').click(function() {
          $(this).addClass('active').siblings().removeClass('active');
	  window.location = $(this).data('href');
      });
  });

  $.fn.create = function() {
      window.location.replace($('#listUrl').val()+'/create');
  }

  $.fn.delete = function() {
      alert('delete');
  }

})(jQuery);
