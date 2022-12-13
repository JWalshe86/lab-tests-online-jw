(function ($, Drupal) {

  /**
   * Behavior for Order Your Test sticky CTA
   */
  Drupal.behaviors.orderYourTestSticky = {
    attach: function (context, settings) {
      var desktop = $(context).find('.oyt-cta-section');
      var desktopBreak = $(context).find('.oyt-cta-section .oyt-cta-block');
      var desktopStepper = $(context).find('.oyt-cta-section .oyt-cta-parent');
      var desktopInner = $(context).find('.oyt-cta-section .oyt-steps-block');
      var desktopLower = $(context).find('.oyt-cta-section .oyt-cta-parent');
      var sticky = $(context).find('.order-your-test-cta-section.order-your-test-sticky');
      var desktopLogos = $(context).find('.oyt-cta-section .oyt-ul-logo-column');
      var globalTestsHeaderText = $(context).find('global-tests-header-text');
      var globalCTABlockCpation = $(context).find('.cta-block-caption');

      desktopBreak.data("bottom", desktop.offset().top + desktop.height());
      $(window).scroll(function () {
        desktopBreak.data("bottom", desktop.offset().top + desktop.height());
        if ($(window).scrollTop() >= desktopBreak.data("bottom")) {
          $(sticky).show();
        } else {
          $(sticky).hide();
        }
      });

      $(window).scroll();

      $(window).on("resize", function () {
        if ($(window).width() <= 1200) {
          if ($('.cc_banner').length) {
            var height = $('.cc_banner').outerHeight();
            $(sticky).css('bottom', height + 'px');
          } else {
            $(sticky).css('bottom', 0);
          }

          $(desktopInner).show();
          $(desktopLower).hide();
          $(desktopStepper).hide();
          $(globalTestsHeaderText).hide();
          $(globalCTABlockCpation).hide();
        } else {
          $(sticky).hide();
          $(sticky).css('bottom', 'inherit');
          $(desktopInner).hide();
          $(desktopLower).show();
          $(desktopStepper).show();
          $(globalTestsHeaderText).show();
          $(globalCTABlockCpation).show();
        }
        if ($(window).width() <= 976) {
          $(desktopLogos).hide();
        } else {
          $(desktopLogos).show();
        }
      }).resize();

      $(window).on('load', function () {
        if ($(window).width() <= 1200) {
          if ($('.cc_banner').length) {
            var height = $('.cc_banner').outerHeight();
            $(sticky).css('bottom', height + 'px');
          }
        }

        $('.cc_btn.cc_btn_accept_all').click(function () {
          if ($(window).width() <= 1200) {
            $(sticky).css('bottom', 0);
          }
        });
      });

    }
  };
})(jQuery, Drupal);
