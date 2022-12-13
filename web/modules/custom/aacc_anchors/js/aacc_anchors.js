/**
 * @file
 * JavaScript behaviors for Anchors feature.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Initialize anchors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.aaccAnchors = {
    attach: function (context) {
      // Function to open an accordion anchor element.
      var openAccordion = function(hash) {
        var $anchor = $(hash, context);

        if (!$anchor.length) {
          return;
        }

        // Speed and delay set to defaults of Foundation 6 Accordion.
        var slideSpeed = 250;
        var slideDelay = 300;

        // Get the accordion where that the anchor resides in.
        var $accordion_item = $anchor.closest('li.accordion-item');
        var $accordion_content = $accordion_item.find('.accordion-content');
        var $accordion = $accordion_item.closest('ul.accordion-element');

        // Close all open accordions first so a correct height can be obtained.
        var $opened_accordions = $('li.accordion-item.is-active', context);

        if ($opened_accordions.length) {
          $opened_accordions.each(function() {
            var $self = $(this);
            var $parent = $self.closest('ul.accordion-element');
            var $content = $self.find('.accordion-content');

            // Close the accordion using Foundation and then delay to give
            // time for DOM height to update for closing transition.
            $parent.foundation('up', $content);
            window.setTimeout(function() {}, slideDelay);
          });
        }

        // Open the accordion using Foundation and then delay to give
        // time for DOM height to update for opening transition.
        $accordion.foundation('down', $accordion_content);

        window.setTimeout(function() {
          // If the hash is not an accordion defined anchor, jump the
          // actual anchor location.
          var offset = (hash.indexOf("#accordion-") >= 0)
              ? $accordion_item.offset()
              : $anchor.offset();

          $('html, body').animate({ scrollTop:  (offset.top - 100)}, slideSpeed);
        }, slideDelay);
      };

      // Anchor action for all relevant links.
      $('a[href*=\\#]', context).click(function (e) {
        var href = $(this).attr('href');

        // Apply anchor functionality to only same page anchor links.
        if (this.pathname === window.location.pathname &&
            this.protocol === window.location.protocol &&
            this.host === window.location.host) {

          var hash = href.split("#")[1];

          if (hash === '') {
            return;
          }

          e.preventDefault();
          openAccordion('#' + hash);
        }
      });

      // Get the anchor from the Browser URL.
      var hash = window.location.hash.substr(1);

      if (hash === '') {
        return;
      }

      openAccordion('#' + hash);
    }
  };

})(jQuery, Drupal);