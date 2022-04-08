(function($) {

  // Run a function when the page is fully loaded including graphics.
  $(window).on('load', function() {
      $('.date').daterangepicker({
          'singleDatePicker': true,
          'timePicker': true,
          'timePicker24Hour': true,
          locale: {
              format: 'DD/MM/YYYY HH:mm',
          }
      },
      function(start, end, label) {
        //console.log('New date range selected: ' + start.format('YYYY-MM-DD h:mm A') + ' to ' + end.format('YYYY-MM-DD h:mm A') + ' (predefined range: ' + label + ')');
      });

      $('.date').on('apply.daterangepicker', function(ev, picker) {
          // Convert the selected datetime in MySQL format. 
          let datetime = picker.startDate.format('YYYY-MM-DD HH:mm');

          // Set the hidden field to the selected datetime
          $('#_'+$(this).attr('id')).val(datetime);
      });

      $('.date').on('show.daterangepicker', function(ev, picker) {
          //$('.daterangepicker').hide();
      });

      $.fn.initStartDates();   
  });

  $.fn.initStartDates = function() {
      // The fields to initialized.
      let fields = ['created_at', 'updated_at'];
      
      for (let i = 0; i < fields.length; i++) {
          // Check first whether the element exists.
          if ($('#'+fields[i]).length) {
              // Set to the current date.
              let startDate = moment().format('DD/MM/YYYY HH:mm');

              // A datetime has been previously set.
              if ($('#'+fields[i]).data('date') != 0) {
                  // Concatenate date and time dataset parameters. 
                  let datetime = $('#'+fields[i]).data('date')+' '+$('#'+fields[i]).data('time');
                  startDate = moment(datetime).format('DD/MM/YYYY HH:mm');
                  // Set the hidden field to the datetime previously set.
                  $('#_'+fields[i]).val(datetime);
              }
              else {
                  // Set the hidden field to the current datetime
                  $('#_'+fields[i]).val(moment().format('YYYY-MM-DD HH:mm'));
              }

              // Initialize the date field.
              $('#'+fields[i]).data('daterangepicker').setStartDate(startDate);
          }
      }
  }

})(jQuery);

