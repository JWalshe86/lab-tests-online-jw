/**
 * @file
 * JavaScript behaviors for AACC Feeds feature.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize anchors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.aaccFeedsAdmin = {
    attach: function (context) {
      var $selects = $('select[multiple="multiple"]', context);

      var updateTotal = function ($select) {
        var $container = $select.closest('.form-item');
        var $label = $container.find('label');
        var $total = $container.find('.feed-select-total');

        if (!$total.length) {
          $label.after('<div class="feed-select-total"><span class="feed-select-total-selected"></span> of <span class="feed-select-total-all"></span> options selected.</div>');
          $total = $container.find('.feed-select-total');
          console.log($total);
        }

        var total_options = $select.find('option:not(option[value="_none"])').length;
        var total_options_selected = $select.find('option[selected="selected"]:not(option[value="_none"])').length;

        $('span.feed-select-total-all', $total).html(total_options);
        $('span.feed-select-total-selected', $total).html(total_options_selected);

        // Update to select to _none if the multiselect is cleared to match
        // existing functionality.
        var $multiselect_sel = $('.improvedselect_sel', $container);

        if ($multiselect_sel.length && $.trim($multiselect_sel.html()) === '') {
          $('option[value="_none"]', $select).attr('selected', 'selected');
        }
      };

      $selects
          .on('change', function (e) {
            updateTotal($(this));
          })
          .each(function () {
            updateTotal($(this));
          });
    }
  };

})(jQuery, Drupal);