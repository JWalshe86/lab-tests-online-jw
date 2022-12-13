(function ($, Drupal) {
  Drupal.behaviors.search_advanced_links = {
    attach: function (context, settings) {
      var form = jQuery('#views-exposed-form-search-main-search');
      form.find('.search-sort').on('click', function (event) {
        event.preventDefault();
        form.find('input[name="sort_by"]').val(jQuery(this).attr('search'));
        form.submit();
      });
      form.find('.search-filter').on('click', function (event) {
        event.preventDefault();
        form.find('input[name="type"]').val(jQuery(this).attr('search'));
        form.submit();
      });
    }
  }
})(jQuery, Drupal);