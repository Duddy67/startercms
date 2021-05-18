(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      let actions = ['create', 'destroy'];

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

  $.fn.create = function() {
      window.location.replace($('#listUrl').val()+'/create');
  }

  $.fn.destroy = function() {
      let ids = [];
      $('.form-check-input:checkbox:checked').each(function () {
	  //var sThisVal = (this.checked ? $(this).val() : "");
      });

      alert('destroy');
  }

})(jQuery);
