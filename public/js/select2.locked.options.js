/**
 * Fix unselecting select2 issue
 * Source : https://github.com/select2/select2/issues/3320
 * https://stackoverflow.com/questions/16564425/how-to-implement-select2-with-lock-selection/48111387#48111387
 *
 * Restore locked option
 * Issue : https://github.com/select2/select2/issues/3339
 */

/* # Update #
 * The suggested code only works with one multiple select tag. If two or more multiple select tags are present on the same page, 
 * the code (for whatever reason) creates a mismatch between the Select2 rendering ids. 
 * This code has been replaced by a more straighforward (but efficient) jQuery code.
 *
 */

$(function() {

   $('._locked').each(function() { 
       // Get the id of the select tag.
       let selectId = $(this).attr('id');
       let selectedOptions = 0;
       let select2Ids = [];

       $('#'+selectId+' option').each(function() {
	   // First check for selected options.
	   if ($(this).attr('selected') !== undefined) {
               // Store the select2 ids of the locked options.
	       if ($(this).attr('locked') !== undefined) {
		   select2Ids.push($(this).data('select2-id'));
	       }

               // Increment the number of options selected.
	       selectedOptions++;
	   }
       });

       if (selectedOptions && select2Ids.length) {
	   // Get the first ul element nested in the span elements.
	   let ul = $('#'+selectId).next('span').find('ul');

	   // Loop through the li tags.
	   $('#'+selectId).next('span').find('ul li').each(function() {
	       if ($(this).attr('class') == 'select2-selection__choice') {
		   for (let i = 0; i < select2Ids.length; i++) {
		       // Compute the final select2 rendering id (ie: the original id plus the number of selected options).
		       let renderingId = select2Ids[i] + selectedOptions;
		       if ($(this).data('select2-id') == renderingId) {
			   // Add the 'locked-tag' class to be able to style element in select.
			   $(this).addClass('locked-tag');
		       }
		   }
	       }
	   });
       }
   });
});

