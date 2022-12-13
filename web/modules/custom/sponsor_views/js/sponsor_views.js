(function ($) {
  Drupal.behaviors.SponsorsMobileAccordion = {
    attach: function (context) {
      if(window.location.hash && screen.width < 768) {
        var hash = window.location.hash;
        $('#sponsor-tabs a[href="' + hash + '"]', context).once('SponsorsMobileAccordion').trigger('click');
      }
    }
  };
})(jQuery);