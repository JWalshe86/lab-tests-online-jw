/**
 * @file
 * Provides functionality to toggle webform items.
 *
 */
(function ($, Drupal) {

/**
 * Behavior for Webform Toggle
 */
Drupal.behaviors.webformToggle = {
  attach: function(context, settings) {
    $(context).find('.webform-toggle').once('toggleWebform').each(function () {
      var $this = $(this);
      var bodyClasses = $('body').attr('class');
      var isUSsite = false;
      if (bodyClasses.search("site--lto-us") >= 0) {
        isUSsite = true;
      }
      if (!isUSsite) {
        $('.ask-us-webform-hidden').css('display', 'none');
      }
      $this.on('click', function(e){
        e.preventDefault();
        // Toggle the see more and see less links.
        $this.find('span').toggleClass('ask-us-webform-hidden-more-button');
        // Toggle hidden webform items and submit button.
        $this.closest('form').parent()[0].scrollIntoView(true);
        $this.closest('form').parent().focus();
        $this.siblings('.ask-us-form-elements').find('.ask-us-webform-hidden.form-wrapper').slideToggle();
        $this.closest('form').parent()[0].scrollIntoView(true);
        $this.closest('form').parent().focus();
        // Toggle class on see more and see less wrapper.
        $this.toggleClass('is-open');
      });
    });
  }
};

})(jQuery, Drupal);
