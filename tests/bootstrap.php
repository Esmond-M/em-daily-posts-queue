<?php
/**
 * Database-free bootstrap. Never load wp-load.php or the WP test installer.
 */

$edpq_root = dirname(__DIR__);
if (!is_file($edpq_root . '/vendor/autoload.php')) {
    throw new RuntimeException('Missing test dependencies. Run composer install first.');
}

require_once $edpq_root . '/vendor/autoload.php';
require_once $edpq_root . '/classes/class-photo-submission-utils.php';
