/**
 * Fix unselecting select2 issue
 * Source : https://github.com/select2/select2/issues/3320
 * https://stackoverflow.com/questions/16564425/how-to-implement-select2-with-lock-selection/48111387#48111387
 *
 * Restore locked option
 * Issue : https://github.com/select2/select2/issues/3339
 */

$(function() {
   $('._locked').select2({
     tags: true,
     placeholder: 'Select an option',
     templateSelection : function (tag, container){
       // here we are finding option element of tag and
        // if it has property 'locked' we will add class 'locked-tag' 
        // to be able to style element in select
       var $option = $('._locked option[value="'+tag.id+'"]');
        if ($option.attr('locked') !== undefined){
           $(container).addClass('locked-tag');
           tag.locked = true; 
        }
        return tag.text;
     },
   })
   .on('select2:unselecting', function(e){
     // before removing tag we check option element of tag and 
      // if it has property 'locked' we will create error to prevent all select2 functionality
       if ($(e.params.args.data.element).attr('locked')) {
           e.select2.pleaseStop();
        }
     });
});
