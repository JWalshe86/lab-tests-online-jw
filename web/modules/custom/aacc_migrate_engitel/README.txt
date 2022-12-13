Install the PHP Driver for SQL Server https://www.microsoft.com/en-us/sql-server/developer-get-started/php/ubuntu/.

Set up configuration in local.settings.php

$config['aacc_migrate_engitel.settings'] = array (
	'databases' => array(
    'source' => array(
      'database' => 'dbLTO_ES_exp',
      'username' => 'SA',
      'password' => '',
      'servername' => '10.0.2.2',
      'driver' => 'SQLSRV',
    ),
	),
  'language' => 'en'
);

Enable module.

Run ../scripts/migrate/migrate_engitel.sh {site-name}
ex. ../scripts/migrate/migrate_engitel.sh aacc-lto.us

To rollback migration run ../scripts/migrate/migrate_engitel_rollback.sh {site_name}