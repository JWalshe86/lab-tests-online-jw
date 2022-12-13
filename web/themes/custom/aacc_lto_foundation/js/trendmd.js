(function ($, Drupal) {

  /**
   * Add TrendMD Markup
   */
  Drupal.behaviors.trendMD = {
    attach: function(context, settings) {
      var isShowAds = getIsShowAds();
      if (isShowAds) {
        if ($('body').hasClass('node--type-screening')) {
          $('#Screening_Recommendations', context).once('trendmd-suggestions-added').after('<div id="trendmd-suggestions"></div>');
        } else if ($('body').hasClass('node--type-news-item')){
          $('.related-content-wrapper', context).once('trendmd-suggestions-added').after('<div id="trendmd-suggestions"></div>');
        } else {
          $('#Related_Content', context).once('trendmd-suggestions-added').after('<div id="trendmd-suggestions"></div>');
        }

      }
    }
  };

})(jQuery, Drupal);
