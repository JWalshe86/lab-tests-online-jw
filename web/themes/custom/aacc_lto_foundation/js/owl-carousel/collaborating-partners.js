(function ($) {
  Drupal.behaviors.AACCOwlCarousel = {
    attach: function (context, settings) {
      var sponsorCarousel = $(context).find('.collaborating-partners-slider').once('collaboratingSponsorSlider');
      var sponsorLogo = sponsorCarousel.find('img').length;
      if (sponsorLogo > 2) {
        sponsorCarousel.owlCarousel({
          loop: true,
          margin:10,
          nav: true,
          autoplay: true,
          navText: [
            "<span class='icon-arrow-1'></span>",
            "<span class='icon-arrow-1'></span>"
          ],
          responsive: {
            0: {
              items: 2,
              loop: (sponsorLogo > 2)
            },
            768: {
              items: 4,
              loop: (sponsorLogo > 4)
            },
            1024: {
              items: 6,
              loop: (sponsorLogo > 6)
            }
          }
        });
      }
    }
  };
})(jQuery);
