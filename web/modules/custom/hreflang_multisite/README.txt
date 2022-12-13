# Center Site settings.local.php settings required.

The country site must have each country site's database configuration entered with the hreflang_multisite and country code keys. The country
code keys should match the country code required for the hreflang region. You must include the center site.
Get key from https://www.aleydasolis.com/english/international-seo-tools/hreflang-tags-generator/  **-** don't use first two letters
Entries for all 9 sites must be defined here!!

$databases['hreflang_multisite']['us'] = array (
  'database' => 'drupalus',
  'username' => 'drupalus',
  'password' => 'drupalus',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql'
);

$databases['hreflang_multisite']['kr'] = array (
  'database' => 'drupalkr',
  'username' => 'drupalkr',
  'password' => 'drupalkr',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql'
);

$databases['hreflang_multisite']['br'] = array (
  'database' => 'drupalbr',
  'username' => 'drupalbr',
  'password' => 'drupalbr',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql'
);

$databases['hreflang_multisite']['gb'] = array (
  'database' => 'drupaluk',
  'username' => 'drupaluk',
  'password' => 'drupaluk',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql'
);

# Additional settings in the center site.

Each site must contain information for the site URL, country code, and default language. This includes the center site.
They arrays must be keyed by the country code.
country_code = key
Entries for all 9 sites must be defined here!

$settings['hreflang_multisite'] = [
  'kr' => [
    'site_url' => 'aacc-lto.kr',
    'country_code' => 'kr',
    'default_language' => 'ko'
  ],
  'br' => [
    'site_url' => 'aacc-lto.br',
    'country_code' => 'br',
    'default_language' => 'pt-br'
  ],
  'us' => [
    'site_url' => 'aacc-lto.us',
    'country_code' => 'us',
    'default_language' => 'en',
    'center_site' => TRUE
  ],
  'gb' => [
    'site_url' => 'aacc-lto.uk',
    'country_code' => 'gb',
    'default_language' => 'en'
  ]
];


# Country site settings

The following settings must be placed into each country sites settings.local.php. The rest center url is the URL of the center site.
The rest username and password is the username and password of the user on the center site with the web service role.

We use this with basic authentication in the rest resource.

$settings['hreflang_multisite_rest'] = [
  'rest_center_url' => 'https://domain-of-center-site.com',
  'rest_username' => 'username',
  'rest_password' => 'password'
];
