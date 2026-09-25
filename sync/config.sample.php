<?php
/**
 * Sample config for the sync suite. Copy to `config.php` and fill in real values.
 * `config.php` is git-ignored (contains DB password + API keys). Never commit secrets.
 *
 * API keys can also come from ENV or 0600 files:
 *   VOLTAPP_API_KEY  or  sync/.apikey
 *   DISTRIB_API_KEY  or  sync/.distrib_apikey
 */
return [
    'db' => [
        'host' => 'localhost',
        'user' => 'YOUR_DB_USER',
        'pass' => 'YOUR_DB_PASSWORD',
        'name' => 'ebs',
    ],

    'voltapp' => [
        'base'    => 'https://api.voltapp.ro',
        'api_key' => getenv('VOLTAPP_API_KEY') ?: trim(@file_get_contents(__DIR__ . '/.apikey')),
    ],

    'apih' => [
        'url'  => 'https://USER:PASSWORD@host/apih.php',  // basic-auth in URL
        'from' => '2026-01-01',
    ],

    'distributie' => [
        'base'    => 'https://api-webfrz.distributie-energie.ro',
        'api_key' => getenv('DISTRIB_API_KEY') ?: trim(@file_get_contents(__DIR__ . '/.distrib_apikey')),
        'take'    => 2000,
    ],

    'enabled_customers'  => [],   // empty = all
    'max_rows_per_batch' => 5000,
];
