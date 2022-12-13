(function ($) {
  Drupal.behaviors.CorporateSponsors = {
    attach: function (context, settings) {
      var corporateSponsor = $('.owl-carousel.corporate-sponsors-slider');
        corporateSponsor.owlCarousel({
          loop: true,
          margin: 10,
          autoplay: true,
          autoPlayTimeout: 1000,
          items: 1
        });
      }
  };
})(jQuery);
