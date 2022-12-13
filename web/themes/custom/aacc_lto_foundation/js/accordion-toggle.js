/**
 * @file
 * Provides functionality to toggle accordion Paragraph items.
 *
 */
(function ($, Drupal) {

/**
 * Behavior for Accordion Toggle
 */
Drupal.behaviors.accordionToggle = {
  attach: function(context, settings) {
    $(context).find('.accordion-toggle').once('toggleAccordion').each(function () {
      var $this = $(this);
      $this.on('click', function(e){
        e.preventDefault();
        // Toggle class on see more and see less wrapper
        $this.toggleClass('is-open');

        // Toggle the see more and see less links.
        $this.find('span').toggleClass('accordion-hidden');
        // Toggle hidden accordion items.
        $this.siblings('.accordion-element').children('.accordion-hidden').slideToggle();
        $this.siblings('.accordion-element').children('.item-hidden').slideToggle();
      });
    });
  }
};

})(jQuery, Drupal);
