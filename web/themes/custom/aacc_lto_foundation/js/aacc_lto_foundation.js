/**
 * @file
 * Placeholder file for custom sub-theme behaviors.
 *
 */
(function ($, Drupal) {

  /**
   * Behavior for Mobile Menu Toggle
   */
  Drupal.behaviors.mobileMenuToggle = {
    attach: function (context, settings) {
      $('.menu-title-bar .button').on('click', function() {
        $(this).toggleClass('is-open');
        $('.mobile-search-container').css('display','none');
        $('.search-title-bar .button').removeClass('is-open');
      });

      $('.search-title-bar .button').on('click', function() {
        $(this).toggleClass('is-open');
        $('.main-nav-container').css('display','none');
        $('.menu-title-bar .button').removeClass('is-open');
      });

      $('.language-button').on('click', function() {
        $(this).toggleClass('is-open');
      });
    }
  };

  /**
   * Behavior for Top News Toggle
   */
  Drupal.behaviors.topNewsToggle = {
    attach: function (context, settings) {
      $(context).find('.home-top-news .views-row').each(function () {
        $(this).on('click', function(event) {
          $(this).toggleClass('news-open');
        });
      });
    }
  };

})(jQuery, Drupal);
