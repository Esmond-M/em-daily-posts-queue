<?php
/** Explicit test-only configuration. Never fall back to a working site's database. */

if ('1' !== getenv('EDPQ_RUN_INTEGRATION') || !preg_match('/^edpq_tests_[a-f0-9]{12}$/', (string) getenv('EDPQ_TEST_DB_NAME'))) {
    throw new RuntimeException('Use tests/run-integration.ps1 to create a disposable integration database.');
}
if (!preg_match('/^127\.0\.0\.1:[0-9]+$/', (string) getenv('EDPQ_TEST_DB_HOST'))) {
    throw new RuntimeException('An explicit loopback test database port is required.');
}

define('ABSPATH', dirname(__DIR__, 2) . '/wordpress/');
define('DB_NAME', getenv('EDPQ_TEST_DB_NAME'));
define('DB_USER', 'root');
define('DB_PASSWORD', getenv('EDPQ_TEST_DB_PASSWORD'));
define('DB_HOST', getenv('EDPQ_TEST_DB_HOST'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_TESTS_DOMAIN', 'edpq-tests.example');
define('WP_TESTS_EMAIL', 'admin@edpq-tests.example');
define('WP_TESTS_TITLE', 'Disposable queue permission tests');
define('WP_PHP_BINARY', escapeshellarg(PHP_BINARY));
define('WP_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'local');
$table_prefix = 'edpqtests_';
