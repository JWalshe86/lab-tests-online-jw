<?php

/**
 * @file
 * Provides the order your test page HTML.
 *
 * Implemented as a #lazy_builder.
 * @see https://www.drupal.org/docs/drupal-apis/render-api/auto-placeholdering.
 */

$buttonLinkDesktop = '';
$buttonLinkMobile = '';
if ($uri !== '' && $uri !== NULL) {
  $buttonLinkDesktop = <<<EOT
    <a
    class="oyt-cta-button"
    href="$uri"
    title="$title"
    target="_blank">
      <img class="cart2-img" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/Cart2.svg" />
      <span class="oyt-button-text">
        $title
      </span>
      <img class="external-link-img" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/ExternalLink.svg" />
    </a>
  EOT;

  $buttonLinkMobile = <<<EOT
    <a
      class="order-your-test-cta-button"
      href="$uri"
      title="$title"
      target="_blank">
      <img class="cart2-img" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/Cart2.svg" />
      <span class="order-your-test-button-text">
        $title
      </span>
      <img class="external-link-img" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/ExternalLink.svg" />
    </a>
  EOT;
}

switch ($layout) {
  case 'short':
    $template = <<<EOT
  <section aria-label="Order Your Test" class="oyt-cta-section cta-short">
    <div class="oyt-cta-block">
    <div class="order-your-test-cta-parent">
      <div class="order-your-test-cta-column order-your-test-button-column">
        <div class="order-your-test-column-main">
          $buttonLinkMobile
        </div>
        <div class="order-your-test-column-subtext">
          Powered by HealthTestingCenters.com
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/1_OrderTest-icon.svg">1</div>
        <div class="order-your-test-column-main">
          <p>
            ORDER YOUR TEST
          </p>
        </div>
        <div class="order-your-test-column-subtext">
          Online or over the phone, <br/>
          no doctor's referral <br/>
          needed
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/2_Lab-icon.svg">2</div>
        <div class="order-your-test-column-main">
          <p>FIND A LAB NEAR YOU</p>
        </div>
        <div class="order-your-test-column-subtext">
          Over 3,500 locations <br/>to choose from
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/3_Results-icon.svg">3</div>
        <div class="order-your-test-column-main">
          <p>GET YOUR RESULTS</p>
        </div>
        <div class="order-your-test-column-subtext">
          Most test results sent <br/>
          directly to you in 1-2 days
        </div>
      </div>
    </div>


    </div>
    <div class="order-your-test-button-column-mobile">
      <div class="order-your-test-column-main">
        $buttonLinkMobile
      </div>
      <div class="order-your-test-column-subtext">
        Powered by HealthTestingCenters.com
      </div>
    </div>

    <div class="oyt-cta-parent">
    </div>
  </section>

  <section aria-label="Order Your Test" class="order-your-test-cta-section order-your-test-sticky">
    <div class="order-your-test-cta-parent">
      <div class="order-your-test-cta-column order-your-test-button-column">
        <div class="order-your-test-column-main">
          $buttonLinkMobile
        </div>
        <div class="order-your-test-column-subtext">
          Powered by HealthTestingCenters.com
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/1_OrderTest-icon.svg">1</div>
        <div class="order-your-test-column-main">
          <p>
            ORDER YOUR TEST
          </p>
        </div>
        <div class="order-your-test-column-subtext">
          Online or over the phone, <br/>
          no doctor's referral <br/>
          needed
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/2_Lab-icon.svg">2</div>
        <div class="order-your-test-column-main">
          <p>FIND A LAB NEAR YOU</p>
        </div>
        <div class="order-your-test-column-subtext">
          Over 3,500 locations <br/>to choose from
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/3_Results-icon.svg">3</div>
        <div class="order-your-test-column-main">
          <p>GET YOUR RESULTS</p>
        </div>
        <div class="order-your-test-column-subtext">
          Most test results sent <br/>
          directly to you in 1-2 days
        </div>
      </div>
    </div>
    <div class="order-your-test-button-column-mobile">
      <div class="order-your-test-column-main">
        $buttonLinkMobile
      </div>
      <div class="order-your-test-column-subtext">
        Powered by HealthTestingCenters.com
      </div>
    </div>
  </section>
EOT;
    break;

  case 'large':
  default:
    $template = <<<EOT
  <section aria-label="Order Your Test" class="oyt-cta-section">
    <div class="oyt-cta-block">
      <div class="oyt-heading-body">
        <h2>Looking to get tested?</h2>
        <p class="global-tests-header-text">More than 500+ lab tests available online - confidential, convenient and affordable; no doctor’s referral needed, no insurance required</p>
      </div>
      <div class="oyt-row">
        <div class="oyt-ul-column">
          <ul>
            <li>Secure and Confidential Results</li>
            <li>No Insurance or Referral Needed</li>
            <li>Affordable Pricing including Doctor's Order</li>
            <li>100% Satisfaction Guarantee</li>
          </ul>

          <div class="oyt-steps-block">
            <div class="oyt-cta-column-inner">
              <div class="oyt-step-inner">
                <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/1_OrderTest-icon.svg" />
                1</div>
              <div class="oyt-column-main-inner">
                <p>
                  ORDER YOUR TEST
                </p>
              </div>
              <div class="oyt-column-subtext-inner">
                Online or over the phone, no doctor's referral needed
              </div>
            </div>
            <div class="oyt-cta-column-inner">
              <div class="oyt-step-inner">
                <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/2_Lab-icon.svg" />
                2</div>
              <div class="oyt-column-main-inner">
                <p>FIND A LAB NEAR YOU</p>
              </div>
              <div class="oyt-column-subtext-inner">
                Over 3,500 locations to choose from
              </div>
            </div>
            <div class="oyt-cta-column-inner">
              <div class="oyt-step-inner">
                <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/3_Results-icon.svg" />
                3</div>
              <div class="oyt-column-main-inner">
                <p>GET YOUR RESULTS</p>
              </div>
              <div class="oyt-column-subtext-inner">
                Most test results sent directly to you in 1-2 days
              </div>
            </div>
          </div>

          <div class="oyt-cta-column oyt-button-column">
            <div class="oyt-column-main">
              $buttonLinkDesktop
            </div>
            <div class="oyt-column-subtext">
              Powered by HealthTestingCenters.com
            </div>
          </div>
        </div>
        <div class="oyt-ul-logo-column">
          <img class="sidebar-logo" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/BBB-logo.png">
          <img class="sidebar-logo" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/TrustedSite-logo.png">
          <img class="sidebar-logo" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/HSA-FSA.png">
        </div>
      </div>
    </div>

    <div class="oyt-cta-parent">
      <div class="oyt-cta-column">
        <div class="oyt-step">
          <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/1_OrderTest-icon.svg" />
          1</div>
        <div class="oyt-column-main">
          <p>
            ORDER YOUR TEST
          </p>
        </div>
        <div class="oyt-column-subtext">
          Online or over the phone, no <br/> doctor's referral needed
        </div>
      </div>
      <div class="oyt-cta-column">
        <div class="oyt-step">
          <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/2_Lab-icon.svg" />
          2</div>
        <div class="oyt-column-main">
          <p>FIND A LAB NEAR YOU</p>
        </div>
        <div class="oyt-column-subtext">
          Over 3,500 locations to choose from
        </div>
      </div>
      <div class="oyt-cta-column">
        <div class="oyt-step">
          <img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/3_Results-icon.svg" />
          3</div>
        <div class="oyt-column-main">
          <p>GET YOUR RESULTS</p>
        </div>
        <div class="oyt-column-subtext">
          Most test results sent directly to you in <br/> 1-2 days
        </div>
      </div>
    </div>

    <div class="cta-block-caption">
      <caption>You can order your own FDA approved laboratory testing online or by phone and walk-in to a local lab location with a lab requisition to have your testing services performed. Direct-access laboratory testing provides the same FDA approved tests ordered by your physician from the same CLIA certified laboratories. You pay private-pay prices with a credit card, online checkout is easy. There are no additional fees for lab services or blood work. We do not bill your health insurance company.</caption>
    </div>
  </section>

  <section aria-label="Order Your Test" class="order-your-test-cta-section order-your-test-sticky">
    <div class="order-your-test-cta-parent">
      <div class="order-your-test-cta-column order-your-test-button-column">
        <div class="order-your-test-column-main">
          $buttonLinkMobile
        </div>
        <div class="order-your-test-column-subtext">
          Powered by HealthTestingCenters.com
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/1_OrderTest-icon.svg">1</div>
        <div class="order-your-test-column-main">
          <p>
            ORDER YOUR TEST
          </p>
        </div>
        <div class="order-your-test-column-subtext">
          Online or over the phone, <br/>
          no doctor's referral <br/>
          needed
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/2_Lab-icon.svg">2</div>
        <div class="order-your-test-column-main">
          <p>FIND A LAB NEAR YOU</p>
        </div>
        <div class="order-your-test-column-subtext">
          Over 3,500 locations <br/>to choose from
        </div>
      </div>
      <div class="order-your-test-cta-column">
        <div class="order-your-test-step"><img class="step-icon" role="presentation" src="/themes/custom/aacc_lto_foundation/images/order-your-test/3_Results-icon.svg">3</div>
        <div class="order-your-test-column-main">
          <p>GET YOUR RESULTS</p>
        </div>
        <div class="order-your-test-column-subtext">
          Most test results sent <br/>
          directly to you in 1-2 days
        </div>
      </div>
    </div>
    <div class="order-your-test-button-column-mobile">
      <div class="order-your-test-column-main">
        $buttonLinkMobile
      </div>
      <div class="order-your-test-column-subtext">
        Powered by HealthTestingCenters.com
      </div>
    </div>
  </section>
EOT;
    break;
}
