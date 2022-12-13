'use strict';

function createCookie(name,value,days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + (days*24*60*60*1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

function getIsShowAds() {
  var thisSiteDomain = (location.host).replace(/^(.*):.*$/, "$1");
  var referrerUrl = document.referrer;
  var rdre = new RegExp('^https*://(.*?)/.*$', 'i');
  var referrerDomain = referrerUrl.replace(rdre, "$1");
  var isDisplayAds = true;
  var externalReferrerDomain = readCookie('ExternalReferrerDomain');

  /* if Ad cookie exists and is set to 'no', then don't show any ads for the rest of the session
   * unless:
   *     1. the referrer domain has changed and
   *     2. the referrer domain is not the current site.
   *
   * Use case: visitor comes to LTO from an ad-free domain, visitor should see no ads and ad cookie is set to 'no';
   *           if visitor then searches for a term from a search engine and clicks on a link to the LTO website,
   *           then ads should be displayed again.  (Session cookie is valid a acroos all browser tabs and is
   *           only auto cleared when entire browser is shut down but not when a tab is closed or re-used
   */
  var isCookieShowAds = readCookie('IsCookieShowAds');
  if (isCookieShowAds && isCookieShowAds == 'no') {
    if (externalReferrerDomain && (externalReferrerDomain != referrerDomain) && (referrerDomain != thisSiteDomain)) {
      /* on a different domain, so set a new external domain and delete Ad cookies;
       the new external domain will undergo domain analysis below */
      createCookie('ExternalReferrerDomain', referrerDomain, 1);
      createCookie('IsCookieShowAds', 'null', 1);
    }
    else {
      /* haven't left the LTO site since coming from the ad-free domain, so continue to not display ads;
       no domain analysis needed */
      isDisplayAds = false;
      return isDisplayAds;
    }
  }

  /* at this stage, go through the domain analysis and determine if Ads should be displayed on this page based on referrer domain;
   also set a session cookie if the referrer domain is in the ad-free list AND the ad cookie does not yet exist */

  /* JS array of ad-free domains and exclusion subdomains (generated by SS) */
  var adFreeDomains = drupalSettings.aacc_ads.aacc_ads.noAdDomains ? drupalSettings.aacc_ads.aacc_ads.noAdDomains.split(', ') : '';
  var adFreeExclusionSubDomains = drupalSettings.aacc_ads.aacc_ads.noAdSubDomains ? drupalSettings.aacc_ads.aacc_ads.noAdSubDomains.split(', ') : '';

  var numAdFreeDomains = adFreeDomains.length;
  var numAdFreeExclusionSubDomains = adFreeExclusionSubDomains.length;

  if (referrerDomain && ((adFreeDomains instanceof Array) && numAdFreeDomains)) {
    /* check if referrer domain is in the ad-free domains array */
    var isExclusionSubDomainMatch = false;
    for (var i = 0; i < numAdFreeDomains; i++) {
      if (referrerDomain.match(new RegExp(adFreeDomains[i], "i"))) {
        isDisplayAds = false;
        if (adFreeExclusionSubDomains instanceof Array && numAdFreeExclusionSubDomains) {
          isExclusionSubDomainMatch = false;
          for (var j = 0; j < numAdFreeExclusionSubDomains; j++) {
            if (referrerDomain.match(adFreeExclusionSubDomains[j])) {
              isDisplayAds = true;
              break;
            }
          }
          break;
        }
      }
    }
    /* if Ad cookie doesn't exist and Ads should not be displayed, then set
     * a session cookie to NOT show ads for the browsing session; also set
     * the external referral domain */
    if (!isCookieShowAds && !isDisplayAds) {
      /* this is a session cookie since there is no 'expires' property set */
      createCookie('IsCookieShowAds', 'no', 1);
      createCookie('ExternalReferrerDomain', referrerDomain, 1);
    }
  }

  return isDisplayAds;
}