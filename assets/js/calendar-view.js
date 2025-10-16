(function($){
  $(function(){
    const calendarContainer = $('#codo-booking-calendar');
    const timePanel = $('#codo-booking-time-panel');
    const selectedDisplay = $('#codo-booking-selected');
    let selectedDate = null;

    function renderTimeSlots(date){
      const slots = codoBookingSlots[date] || [];
      let html = '<div class="codo-time-slots">';
      if(!slots.length){
        html += '<p>No available times.</p>';
      } else {
        slots.forEach(time => {
          html += `<button type="button" class="codo-slot" data-time="${time}">${time}</button>`;
        });
      }
      html += '</div>';
      timePanel.html(html);

      timePanel.find('.codo-slot').off('click').on('click', function(){
        timePanel.find('.codo-slot').removeClass('selected');
        $(this).addClass('selected');
        $('#booking_time').val($(this).data('time'));
        selectedDisplay.html(`<strong>Selected:</strong> ${selectedDate} at ${$(this).data('time')}`);
      });
    }

    // Click date
    $(document).on('click', '.codo-day:not(.disabled)', function(){
      $('.codo-day').removeClass('selected');
      $(this).addClass('selected');
      selectedDate = $(this).data('date');
      $('#booking_date').val(selectedDate);
      renderTimeSlots(selectedDate);
    });

    // Month navigation
    $(document).on('click', '.codo-nav-month', function(){
      const month = $(this).data('month');
      const year = $(this).data('year');

      const header = calendarContainer.find('.codo-calendar-header');
      const grid = calendarContainer.find('.codo-calendar-grid');
      header.hide();
      grid.hide();
      timePanel.html('');
      selectedDisplay.html('');
      header.after('<div class="codo-loading">Loading...</div>');

      $.get(codoBookingsData.ajaxUrl, { action:'render_month', month, year }, function(resp){
        if(resp.success){
          //grid.remove();
          //header.after(resp.data.html);
          calendarContainer.html(resp.data.html);
          timePanel.html('');
          selectedDisplay.html('');
          $('#booking_date,#booking_time').val('');
          selectedDate = null;
        }
      }, 'json').always(function(){
        calendarContainer.find('.codo-loading').remove();
      });
    });
  });
})(jQuery);
