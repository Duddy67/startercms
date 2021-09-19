(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {

      if ($('#main-cat-id').val()) {
	  $.fn.refreshMainCategoryBox();
      }

      $('#categories').on('select2:unselect', function(e) {
	    let nbSelectedOptions = $(this).select2('data').length;

	    if (nbSelectedOptions == 0) {
		$('#main-cat-id').val('');
		e.params.originalEvent.stopPropagation();
	        return;
	    }

	    let data = e.params.data;
	    
	    if (data.id == $('#main-cat-id').val()) {
		$.fn.selectAsMainCategory($(this).select2('data')[0].id);
	    }

	    //console.log($(this).select2('data'));
	    $.fn.refreshMainCategoryBox();
	    // Prevents the dropdown from opening.
	    e.params.originalEvent.stopPropagation();
      });

      $('#categories').on('select2:select', function(e) {
	  let nbSelectedOptions = $(this).select2('data').length;
	  let data = e.params.data;

	  if (nbSelectedOptions == 1) {
	      $.fn.selectAsMainCategory(data.id);

	      return;
	  }

	  $.fn.refreshMainCategoryBox();
      });

      $('#categories').next('span').find('ul').on('click', '.select2-selection__choice', function (e) {
	 //
	 let catId = $(this).attr('title').substr(11);

	 // Check target is actually a li tag, not an embedded span tag (used for unselect boxes).
	 if (e.target == this) {
	     $.fn.selectAsMainCategory(catId);
	 }

	 // Prevents the dropdown from opening.
	 e.stopPropagation();
      });
  });

  $.fn.selectAsMainCategory = function(catId) {
      let oldCatId = $('#main-cat-id').val();

      if (oldCatId == catId) {
	  return;
      }

      $('#main-cat-id').val(catId);

      $('#categories').next('span').find('ul li').each(function() {
	  if ($(this).attr('title') !== undefined && $(this).attr('title').substr(11) == oldCatId) {
	      $(this).css('background-color', '#e4e4e4');
	  }

	  if ($(this).attr('title') !== undefined && $(this).attr('title').substr(11) == catId) {
	      $(this).css('background-color', '#aedef4');
	  }
      });
  }

  $.fn.refreshMainCategoryBox = function() {
      let catId = $('#main-cat-id').val();

      $('#categories').next('span').find('ul li').each(function() {
	    if ($(this).attr('title') !== undefined && $(this).attr('title').substr(11) == catId) {
		$(this).css('background-color', '#aedef4');
	    }
      });
  }

})(jQuery);
