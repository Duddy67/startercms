(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      $('.clickable-row').click(function() {
          $(this).addClass('active').siblings().removeClass('active');
	  window.location = $(this).data('href');
      });
  });

})(jQuery);
