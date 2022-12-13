(function ($) {

  /**
   * Behavior for expandable buttons
   */
  Drupal.behaviors.expandableButton = {
    attach: function (context, settings) {
      var button = $(context).find('div.looking-for-buttons');
      button.find('.expandable-button').on('click', function() {
        $('.expandable-button').not(this).parent().removeClass('is-active');
        $(this).parent().toggleClass('is-active');
      });
    }
  };

})(jQuery);
