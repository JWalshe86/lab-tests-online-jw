Set up configuration in local.settings.php

Database connection: 

$databases['migrate']['default'] = array (
  'database' => 'lto_us',
  'username' => 'root',
  'password' => 'root',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

Language settings:

$config['aacc_migrate.settings'] = array (
  'language' => 'en'
);


Enable module.

Run ../scripts/migrate/migrate.sh {site-name}
ex. ../scripts/migrate/migrate_rollback.sh aacc-lto.us

Uninstall module after testing migrations.