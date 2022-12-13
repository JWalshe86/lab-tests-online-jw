(function ($, Drupal) {
  Drupal.behaviors.aacc_reports = {
    attach: function (context, settings) {
      var $form = $('#views-exposed-form-form-submission-report-page-1', context);
      var $date_filter = $('#edit-date-filter-options', $form);
      var $date_min = $('#edit-date-range-min', $form);
      var $date_max = $('#edit-date-range-max', $form);

      var get_date_string = function(date) {
        return (date.getMonth() + 1) + '/' + date.getDate() + '/' + date.getFullYear();
      };

      var range_custom = function() {
        $date_min.val('').removeAttr('disabled');
        $date_max.val('').removeAttr('disabled');
      };

      var range_last_month = function() {
        var date = new Date();
        date.setMonth(date.getMonth() - 1);
        date.setDate(1);

        $date_min.val(get_date_string(date)).prop('disabled', true);

        date.setMonth(date.getMonth() +1);
        date.setDate(0);

        $date_max.val(get_date_string(date)).prop('disabled', true);
      };

      var range_calendar_year = function() {
        var date = new Date();
        $date_max.val(get_date_string(date)).prop('disabled', true);

        date.setMonth(0);
        date.setDate(1);
        date.setHours(0);
        date.setMinutes(0);
        date.setSeconds(0);

        $date_min.val(get_date_string(date)).prop('disabled', true);
      };

      var range_calendar_year_last = function() {
        var date = new Date();
        date.setMonth(0);
        date.setDate(1);
        date.setFullYear(date.getFullYear() - 1);

        $date_min.val(get_date_string(date)).prop('disabled', true);

        date.setMonth(11);
        date.setDate(31);

        $date_max.val(get_date_string(date)).prop('disabled', true);
      };

      $date_filter.on('change', function(e) {
        var $self = $(this);

        switch($self.val()) {
          case 'custom':
            range_custom();
            break;
          case 'last_month':
            range_last_month();
            break;
          case 'calendar_year_to_date':
            range_calendar_year();
            break;
          case 'calendar_year_last':
            range_calendar_year_last();
            break;

        }
      }).trigger('change');

      $form.on('submit', function(e) {
        $(':disabled', $(this)).each(function(e) {
          $(this).removeAttr('disabled');
        });
      });
    }
  };
})(jQuery, Drupal);