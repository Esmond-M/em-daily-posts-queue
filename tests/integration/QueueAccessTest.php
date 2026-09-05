<?php

final class QueueAccessTest extends WP_UnitTestCase
{
    public function set_up() {
        parent::set_up();
        remove_action('admin_enqueue_scripts', 'wp_auth_check_load');
        $GLOBALS['edpq_test_cpt']->net_submission_role();
        $GLOBALS['edpq_test_cpt']->net_submission_cap();
    }

    public function tear_down() {
        $_GET = $_POST = $_REQUEST = [];
        wp_set_current_user(0);
        parent::tear_down();
        wp_roles()->for_site();
    }

    public function testExistingRolesGainOnlyQueueViewingAndKeepCustomPermissions() {
        foreach (['administrator', 'net_submission_role'] as $name) {
            $role = get_role($name);
            $role->remove_cap('edpq_view_queue');
            $role->add_cap('edpq_test_custom_permission');
            $before[$name] = $role->capabilities;
        }

        $GLOBALS['edpq_test_cpt']->net_submission_cap();
        $GLOBALS['edpq_test_cpt']->net_submission_cap();

        foreach ($before as $name => $capabilities) {
            $capabilities['edpq_view_queue'] = true;
            self::assertEquals($capabilities, get_role($name)->capabilities);
        }
        self::assertFalse(get_role('net_submission_role')->has_cap('manage_options'));
    }

    public function testFreshSubmitterRoleReceivesViewingAndExistingPostPermissions() {
        remove_role('net_submission_role');
        $GLOBALS['edpq_test_cpt']->net_submission_role();
        $GLOBALS['edpq_test_cpt']->net_submission_cap();
        $user = self::factory()->user->create(['role' => 'net_submission_role']);
        wp_set_current_user($user);
        foreach (['edpq_view_queue', 'edit_net_submissions', 'edit_others_net_submissions', 'publish_net_submissions', 'delete_net_submissions', 'upload_files'] as $capability) {
            self::assertTrue(current_user_can($capability), $capability);
        }
        self::assertFalse(current_user_can('manage_options'));
    }

    /** @dataProvider viewers */
    public function testAuthorizedUsersCanRenderQueueAndViewSubmission($role, $canManage) {
        $user = self::factory()->user->create(['role' => $role]);
        wp_set_current_user($user);
        $post = self::factory()->post->create(['post_type' => 'net_submission', 'post_status' => 'publish', 'post_title' => 'Permission test photo']);
        self::assertTrue(current_user_can('read_post', $post));
        ob_start();
        try { $GLOBALS['edpq_test_manager']->edpqadmin_queue_list_page(); }
        finally { $html = ob_get_clean(); }
        self::assertStringContainsString('Permission test photo', $html);
        self::assertStringContainsString(esc_url(get_permalink($post)), $html);
        self::assertStringNotContainsString('page=admin-queue-edit', $html);
        self::assertSame($canManage, strpos($html, 'id="admin-queue-edit-form"') !== false);
        self::assertSame($canManage, strpos($html, 'class="button button-primary"') !== false);
        self::assertSame($canManage, strpos($html, 'id="edpq-discard-queue-changes"') !== false);
        self::assertSame($canManage, strpos($html, 'id="edpq-queue-status"') !== false);
        self::assertSame($canManage, strpos($html, 'tabindex="0"') !== false);
        self::assertSame($canManage, strpos($html, 'id="full-wipe-btn"') !== false);
    }

    public static function viewers() {
        return [['net_submission_role', false], ['administrator', true]];
    }

    /** @dataProvider outsiders */
    public function testUnrelatedUsersCannotRenderQueueDirectly($role) {
        wp_set_current_user($role ? self::factory()->user->create(['role' => $role]) : 0);
        $this->expectException(WPDieException::class);
        $this->expectExceptionCode(403);
        $GLOBALS['edpq_test_manager']->edpqadmin_queue_list_page();
    }

    public static function outsiders() {
        return [['subscriber'], ['editor'], [null]];
    }

    public function testSubmitterCannotInvokeManagementPageWithImportOrScheduleInputs() {
        wp_set_current_user(self::factory()->user->create(['role' => 'net_submission_role']));
        $_GET['import_demo'] = '1';
        $_POST = ['update_cron_time' => '1', 'cron_time_input' => '+1 weekday 8pm'];
        $before = wp_count_posts('net_submission');
        ob_start();
        try {
            $GLOBALS['edpq_test_manager']->edpqadmin_queue_edit_page();
            self::fail('Management callback must reject a direct call.');
        } catch (WPDieException $error) {
            self::assertSame(403, $error->getCode());
        } finally { ob_end_clean(); }
        self::assertEquals($before, wp_count_posts('net_submission'));

        ob_start();
        try { $GLOBALS['edpq_test_manager']->edpqadmin_queue_list_page(); }
        finally { ob_end_clean(); }
        self::assertEquals($before, wp_count_posts('net_submission'));
    }

    public function testQueueListLoadsManagementAssetsForAdministratorsOnly() {
        $saved_pagenow = $GLOBALS['pagenow'] ?? null;
        $_GET = ['post_type' => 'net_submission', 'page' => 'admin-queue-list'];
        $GLOBALS['pagenow'] = 'edit.php';
        $ajax_property = new ReflectionProperty($GLOBALS['edpq_test_manager'], 'ajax');
        $ajax_property->setAccessible(true);
        $ajax = $ajax_property->getValue($GLOBALS['edpq_test_manager']);
        try {
            wp_set_current_user(self::factory()->user->create(['role' => 'net_submission_role']));
            $ajax->load_admin_net_style();
            self::assertTrue(wp_style_is('admin_option_css', 'enqueued'));
            self::assertFalse(wp_script_is('admin-queue-edits-js', 'enqueued'));
            wp_dequeue_style('admin_option_css');
            wp_deregister_style('admin_option_css');

            wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
            $ajax->load_admin_net_style();
            self::assertTrue(wp_style_is('admin_option_css', 'enqueued'));
            self::assertTrue(wp_script_is('admin-queue-edits-js', 'enqueued'));
            self::assertFalse(wp_style_is('font-awesome', 'enqueued'));
            self::assertFalse(wp_style_is('edpq-admin-queue-edit-css', 'enqueued'));
        } finally {
            wp_dequeue_style('admin_option_css');
            wp_deregister_style('admin_option_css');
            wp_dequeue_script('admin-queue-edits-js');
            wp_deregister_script('admin-queue-edits-js');
            if ($saved_pagenow === null) { unset($GLOBALS['pagenow']); } else { $GLOBALS['pagenow'] = $saved_pagenow; }
        }
    }

    public function testUninstallRemovesTheNewAdministratorGrantOnly() {
        require_once dirname(__DIR__, 2) . '/em-daily-posts-queue.php';
        $before = get_role('administrator')->capabilities;
        unset($before['edpq_view_queue']);
        EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::EmDailyPostsQueue_uninstall();
        self::assertEquals($before, get_role('administrator')->capabilities);
        self::assertNull(get_role('net_submission_role'));
    }

    /** @dataProvider menuRoles */
    public function testWordPressMenuAndDirectPageAccess($role, $canView, $canManage) {
        wp_set_current_user(self::factory()->user->create(['role' => $role]));
        $names = ['menu', 'submenu', 'admin_page_hooks', '_wp_submenu_nopriv', '_wp_menu_nopriv', '_registered_pages', '_parent_pages', 'parent_file', 'pagenow', 'plugin_page', 'typenow'];
        foreach ($names as $name) { $saved[$name] = $GLOBALS[$name] ?? null; }
        try {
            $parent = 'edit.php?post_type=net_submission';
            $GLOBALS['menu'] = [[ 'Net submissions', 'edit_net_submissions', $parent ]];
            $GLOBALS['submenu'] = $GLOBALS['_wp_submenu_nopriv'] = $GLOBALS['_wp_menu_nopriv'] = $GLOBALS['_registered_pages'] = $GLOBALS['_parent_pages'] = [];
            $GLOBALS['admin_page_hooks'] = [$parent => 'net_submission'];
            $GLOBALS['parent_file'] = $parent;
            $GLOBALS['pagenow'] = 'edit.php';
            $GLOBALS['typenow'] = 'net_submission';
            $GLOBALS['edpq_test_manager']->edpqPhotoSubmission_register_submenu_page();
            $slugs = array_column($GLOBALS['submenu'][$parent] ?? [], 2);
            self::assertSame($canView, in_array('admin-queue-list', $slugs, true));
            self::assertFalse(in_array('admin-queue-edit', $slugs, true));
            foreach (['admin-queue-list' => $canView, 'admin-queue-edit' => $canManage, 'edpq-shortcodes' => $canManage] as $page => $expected) {
                $GLOBALS['plugin_page'] = $page;
                self::assertSame($expected, user_can_access_admin_page(), $page);
            }
            self::assertSame($canManage ? 10 : false, has_action('load-net_submission_page_admin-queue-edit', [$GLOBALS['edpq_test_manager'], 'redirect_admin_queue_edit_page']));
        } finally {
            foreach ($saved as $name => $value) {
                if ($value === null) { unset($GLOBALS[$name]); } else { $GLOBALS[$name] = $value; }
            }
        }
    }

    public static function menuRoles() {
        return [['net_submission_role', true, false], ['administrator', true, true], ['subscriber', false, false], ['editor', false, false]];
    }
}
