<?php

final class QueueAjaxAccessTest extends WP_Ajax_UnitTestCase
{
    public function set_up() {
        parent::set_up();
        // Core hook restoration can re-add update checks between test cases.
        foreach (['_maybe_update_core', '_maybe_update_plugins', '_maybe_update_themes'] as $callback) {
            remove_action('admin_init', $callback);
        }
    }

    /** @dataProvider protectedActions */
    public function testSubmitterWithValidNonceCannotMutateQueue($action) {
        $post = self::factory()->post->create(['post_type' => 'net_submission', 'post_status' => 'publish']);
        $utils = new EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils();
        $before = $utils->get_queue_list();
        wp_set_current_user(self::factory()->user->create(['role' => 'net_submission_role']));
        $_POST = [
            'nonce' => wp_create_nonce('edpq_admin_queue'),
            'form_data' => '',
            'client_snapshot' => wp_json_encode($before),
            'checkWindowAge' => wp_json_encode($before),
            'remove_postid' => $post,
            'remove_queue' => 1,
        ];
        try { $this->_handleAjax($action); } catch (WPAjaxDieContinueException $error) { }
        $response = json_decode($this->_last_response, true);
        self::assertFalse($response['success']);
        self::assertSame('Permission denied.', $response['data']['message']);
        self::assertSame($before, $utils->get_queue_list());
        self::assertNotNull(get_post($post));
    }

    /** @dataProvider protectedActions */
    public function testAdministratorWithInvalidNonceCannotMutateQueue($action) {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_POST = ['nonce' => 'invalid'];
        $this->expectException(WPAjaxDieStopException::class);
        $this->expectExceptionMessage('-1');
        $this->_handleAjax($action);
    }

    public function testAdministratorCanSaveAnUnchangedQueueWithValidNonce() {
        $post = self::factory()->post->create(['post_type' => 'net_submission', 'post_status' => 'publish']);
        $utils = new EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils();
        $before = $utils->get_queue_list();
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $form = [];
        foreach ($before as $item) {
            $form['queue-postID-' . $item['queueNumber']] = $item['postid'];
            $form['queue-value-' . $item['queueNumber']] = $item['queueNumber'];
        }
        $_POST = ['nonce' => wp_create_nonce('edpq_admin_queue'), 'form_data' => http_build_query($form), 'client_snapshot' => wp_json_encode($before)];
        try { $this->_handleAjax('admin_queue_edit'); } catch (WPAjaxDieContinueException $error) { }
        self::assertTrue(json_decode($this->_last_response, true)['success']);
        self::assertSame($before, $utils->get_queue_list());
        self::assertNotNull(get_post($post));
    }

    public function testAdministratorCannotFullWipeWithoutTypedConfirmation() {
        $post = self::factory()->post->create(['post_type' => 'net_submission', 'post_status' => 'publish']);
        $utils = new EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils();
        $before = $utils->get_queue_list();
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_POST = ['nonce' => wp_create_nonce('edpq_admin_queue')];

        try { $this->_handleAjax('admin_queue_full_wipe'); } catch (WPAjaxDieContinueException $error) { }

        $response = json_decode($this->_last_response, true);
        self::assertFalse($response['success']);
        self::assertSame('Type FULL WIPE to confirm permanent deletion.', $response['data']['message']);
        self::assertSame($before, $utils->get_queue_list());
        self::assertNotNull(get_post($post));
    }

    public function testAdministratorFullWipeDeletesSubmissionsInEveryStatusAndClearsQueue() {
        $post_ids = [];
        foreach (['publish', 'draft', 'pending', 'private', 'trash'] as $status) {
            $post_ids[] = self::factory()->post->create(['post_type' => 'net_submission', 'post_status' => $status]);
        }
        $regular_post_id = self::factory()->post->create(['post_type' => 'post', 'post_status' => 'publish']);

        $utils = new EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionUtils();
        $queue = [];
        foreach ($post_ids as $index => $post_id) {
            $queue[] = ['postid' => $post_id, 'queueNumber' => $index + 1];
        }
        $utils->update_queue_list_in_db($queue);

        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        $_POST = ['nonce' => wp_create_nonce('edpq_admin_queue'), 'confirmation' => 'FULL WIPE'];

        try { $this->_handleAjax('admin_queue_full_wipe'); } catch (WPAjaxDieContinueException $error) { }

        $response = json_decode($this->_last_response, true);
        self::assertTrue($response['success']);
        self::assertSame([], $utils->get_queue_list());
        foreach ($post_ids as $post_id) {
            self::assertNull(get_post($post_id));
        }
        self::assertNotNull(get_post($regular_post_id));
    }

    public static function protectedActions() {
        return [['admin_queue_edit'], ['admin_queue_full_wipe'], ['net_photo_deletion_info_ajax']];
    }
}
