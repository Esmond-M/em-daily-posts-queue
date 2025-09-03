<?php
/**
 * PHPUnit bootstrap file
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// WP test functions
require_once getenv('WP_PHPUNIT__DIR') . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function() {
    // test set up, plugin activation, etc.
});

// WP testing environment
require getenv('WP_PHPUNIT__DIR') . '/includes/bootstrap.php';

// Manually require plugin class files for tests
require_once dirname(__DIR__) . '/classes/class-cron-event-timer.php';
require_once dirname(__DIR__) . '/classes/class-photo-submission-queue-manager.php';
require_once dirname(__DIR__) . '/classes/class-photo-submission-ajax.php';
require_once dirname(__DIR__) . '/classes/class-photo-submission-utils.php';
// Add other class files as needed
