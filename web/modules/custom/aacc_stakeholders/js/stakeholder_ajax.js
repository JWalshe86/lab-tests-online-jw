(function ($, Drupal) {
  Drupal.behaviors.stakeholder_ajax = {
    attach: function (context, settings) {
      if (/bot|google|baidu|bing|msn|duckduckgo|teoma|slurp|yandex/i.test(navigator.userAgent)) {
        // Disable ads for user agents that look like search engine spiders.
        // Prevents ad impressions being wasted on search engines. See:
        // https://perishablepress.com/list-all-user-agents-top-search-engines/
      }
      else {
        jQuery.get('/stakeholders/display/' + drupalSettings.aacc_stakeholders.stakeholder.nid).done(function (data) {
          jQuery('.stakeholder-load').html(data);
        });
      }
    }
  }
})(jQuery, Drupal);
