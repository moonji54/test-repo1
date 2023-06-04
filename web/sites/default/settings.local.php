<?php

/**
 * @file
 * Database credentials for staging environment.
 */

// General settings.
$databases['default']['default'] = [
  'database' => "nrgi_dev_db",
  'username' => "nrgi_dev",
  'password' => "password",
  'host' => "localhost",
  'driver' => "mysql",
  'port' => '3306',
  'init_commands' => [
    'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
  ],
  'prefix' => "",
];

$settings['hash_salt'] = '-Tl4c7mlZGDHogJZeXg1qBvfl02Gig2pGPJ35Gm0WccCbay9fD0UkBEDljg6KPRqyGvz4GFFTQ';
$settings['trusted_host_patterns'] = array('34\.203\.231\.226$',
	'^localhost$',
	'^development\.resourcegovernance\.org$'
);
