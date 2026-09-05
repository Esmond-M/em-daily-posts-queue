<?php

use EmDailyPostsQueue\init_plugin\Classes\EmDailyPostsQueueUIManager;
use PHPUnit\Framework\TestCase;

$edpq_hook_api = dirname(__DIR__, 2) . '/wordpress/wp-includes/plugin.php';
if (!is_file($edpq_hook_api)) {
    throw new RuntimeException('Missing Composer-installed WordPress hook API. Run composer install.');
}

// Real WordPress hooks without booting a site, connecting to MySQL, or scheduling jobs.
require_once $edpq_hook_api;
require_once dirname(__DIR__, 2) . '/classes/class-photo-submission-queue-manager.php';

final class QueueManagerHooksTest extends TestCase
{
    private $savedHooks;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['wp_filter', 'wp_actions', 'wp_filters', 'wp_current_filter'] as $name) {
            $this->savedHooks[$name] = $GLOBALS[$name];
            $GLOBALS[$name] = [];
        }
        new EmDailyPostsQueueUIManager();
    }

    protected function tearDown(): void
    {
        foreach ($this->savedHooks as $name => $value) {
            $GLOBALS[$name] = $value;
        }
        parent::tearDown();
    }

    public function testSubmissionBulkActionsPreserveUnrelatedActions(): void
    {
        self::assertSame(
            ['custom' => 'Custom'],
            apply_filters('bulk_actions-edit-net_submission', ['edit' => 'Edit', 'trash' => 'Trash', 'custom' => 'Custom'])
        );
    }

    public function testSubmissionRowActionsPreserveViewAndEdit(): void
    {
        self::assertSame(
            ['view' => 'View', 'edit' => 'Edit'],
            apply_filters('post_row_actions', [
                'inline hide-if-no-js' => 'Quick Edit',
                'trash' => 'Trash',
                'bulk_edit' => 'Bulk Edit',
                'view' => 'View',
                'edit' => 'Edit',
            ], (object) ['post_type' => 'net_submission'])
        );
    }

    public function testOtherPostTypesKeepTheirRowActions(): void
    {
        $actions = ['inline hide-if-no-js' => 'Quick Edit', 'trash' => 'Trash', 'view' => 'View'];
        self::assertSame($actions, apply_filters('post_row_actions', $actions, (object) ['post_type' => 'post']));
    }

    /** @dataProvider queueMutationActions */
    public function testQueueMutationsHaveNoGuestAjaxRegistration(string $action): void
    {
        self::assertTrue(has_action('wp_ajax_' . $action));
        self::assertFalse(has_action('wp_ajax_nopriv_' . $action));
    }

    public static function queueMutationActions(): array
    {
        return [
            ['net_photo_deletion_info_ajax'],
            ['admin_queue_edit'],
            ['admin_queue_full_wipe'],
        ];
    }

    public function testPublicSubmissionFormKeepsItsGuestRegistration(): void
    {
        self::assertTrue(has_action('wp_ajax_form_post_new_net_photo_submission_ajax'));
        self::assertTrue(has_action('wp_ajax_nopriv_form_post_new_net_photo_submission_ajax'));
    }
}
