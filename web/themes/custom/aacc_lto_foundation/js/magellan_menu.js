
(function ($) {

  /**
   * Render Ask Us link if necessary
   */
  Drupal.behaviors.askUs = {
    attach: function (context, settings) {
      var askUs = $(context).find('.menu-magellan .ask-us');
      var askUsLink = $(context).find('.ask-us a');
      var block = $(context).find('#block-askalaboratoryscientist, #block-ask-us');
      if ($(block).length) {
        $(askUs).removeClass('hide');
      }
      // Smooth scroll to Ask Us
      // $(askUsLink).on('click', function (event) {
      $('.ask-us').click(function(event)  {
        event.preventDefault();
        $('html, body').animate({
          scrollTop: $(block).offset().top
        }, 1000);
      });
    }
  };

  /**
   * Behavior for Magellan Menu Toggle
   */
  Drupal.behaviors.magellanMenu = {
    attach: function (context, settings) {
      var menu = $(context).find('div.menu-magellan');
      menu.find('.menu-magellan-title').on('click', function() {
        menu.children('a').toggleClass('open').parents(menu).find('.menu-magellan-body').toggleClass('hidden');
      });
    }
  };

})(jQuery);
