<?php
/**
 * PhotoNetSubmissionUtils
 *
 * Helper class for Esmond Daily Posts Queue plugin.
 * Provides utility methods for:
 * - Managing the photo submission queue in the database
 * - Comparing multidimensional arrays for queue conflict detection
 * - Importing demo net_submission posts with featured images
 * - Sending admin notification emails
 * - General queue retrieval and update operations
 */
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

class PhotoNetSubmissionUtils {
    /**
     * Decode a stored queue value, migrating legacy serialize+base64 rows to JSON on first read.
     * @param string $raw  The raw `list` column value from the DB.
     * @return array
     */
    private function decode_queue(string $raw): array {
        if ('' === $raw) {
            return [];
        }
        // Try JSON first (new format)
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->filter_queue_items($decoded);
        }
        // Fall back to legacy serialize+base64
        $legacy = @unserialize(base64_decode($raw));
        return is_array($legacy) ? $this->filter_queue_items($legacy) : [];
    }

    /**
     * Filter an array down to valid queue items (must have postid + queueNumber).
     * Discards install/info rows like {"message":"Congratulations..."}.
     */
    private function filter_queue_items(array $items): array {
        return array_values(array_filter($items, function ($item) {
            return is_array($item) && isset($item['postid'], $item['queueNumber']);
        }));
    }

    /**
     * Get the queue list from the database
     * @return array
     */
    public function get_queue_list_from_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT list FROM $table_name WHERE id = %d", 1 ), ARRAY_A );
        if (empty($row['list'])) {
            return [];
        }
        $queue = $this->decode_queue($row['list']);
        // Migrate: if the row was stored as legacy format, write it back as JSON now
        if (json_decode($row['list'], true) === null && !empty($queue)) {
            $this->update_queue_list_in_db($queue);
        }
        return $queue;
    }
    /**
     * Compare two multidimensional arrays and return differences
     * @param array $array1
     * @param array $array2
     * @param bool $strict
     * @return array
     */
    public function edpqcompareMultiDimensional($array1, $array2, $strict = true){
        if (!is_array($array1)) {
            throw new \InvalidArgumentException('$array1 must be an array!');
        }
        if (!is_array($array2)) {
            return $array1;
        }
        $result = array();
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $result[$key] = $value;
                continue;
            }
            if (is_array($value) && count($value) > 0) {
                $recursiveArrayDiff = $this->edpqcompareMultiDimensional($value, $array2[$key], $strict);
                if (count($recursiveArrayDiff) > 0) {
                    $result[$key] = $recursiveArrayDiff;
                }
                continue;
            }
            $value1 = $value;
            $value2 = $array2[$key];
            if ($strict ? is_float($value1) && is_float($value2) : is_float($value1) || is_float($value2)) {
                $value1 = (string) $value1;
                $value2 = (string) $value2;
            }
            if ($strict ? $value1 !== $value2 : $value1 != $value2) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
    * Import demo net_submission posts (4 demo posts)
    */
    public function import_demo_net_submissions() {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        $demo_images = glob($plugin_dir . 'assets/imgs/demo/*.png');
        if (!$demo_images) {
            $demo_images = [$plugin_dir . 'assets/imgs/placeholder.png'];
        }
        for ($i = 1; $i <= 4; $i++) {
            $post_id = wp_insert_post([
                'post_title'   => "Demo Submission $i",
                'post_content' => "This is demo content for submission $i.",
                'post_status'  => 'publish',
                'post_type'    => 'net_submission',
                'meta_input'   => [
                    'topic_headline_value' => "Demo Headline $i",
                    'topic_caption_value'  => "Demo Caption $i"
                ]
            ]);
            // Assign featured image if post creation succeeded
            $demo_image_path = $demo_images[($i - 1) % count($demo_images)];
            if ($post_id && file_exists($demo_image_path)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $upload = wp_upload_bits('edpq-' . sanitize_file_name(basename($demo_image_path)), null, file_get_contents($demo_image_path));
                if (!$upload['error']) {
                    $filetype = wp_check_filetype($upload['file'], null);
                    $attachment = array(
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => sprintf('Demo Image %d', $i),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }
    }

    /**
     * Retrieve the current photo submission queue list from the database
     * @return array
     */
    public function get_queue_list() {
        return $this->get_queue_list_from_db();
    }

    /**
     * Update the queue list in the database
     * @param array $queue_list
     * @return bool
     */
    public function update_queue_list_in_db($queue_list) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
        $result = $wpdb->update(
            $table_name,
            ['list' => wp_json_encode($queue_list)],
            ['id'   => 1],
            ['%s'],
            ['%d']
        );
        return $result !== false;
    }

    /**
     * Helper to send notification email to admin
     */
    public function send_admin_email($subject, $message) {
        $emailto = get_option('admin_email');
        wp_mail($emailto, $subject, $message);
    }

    const DEMO_SUBMITTER_LOGIN = 'edpq_demo_submitter';

    /**
     * Create the reserved demo Net Submitter test account, or reset its password if it already exists.
     * @return array{login:string,password:string,created:bool}
     */
    public function create_or_reset_demo_submitter(): array {
        $password = wp_generate_password(20, true, true);
        $user = get_user_by('login', self::DEMO_SUBMITTER_LOGIN);

        if ($user) {
            wp_set_password($password, $user->ID);
            return ['login' => self::DEMO_SUBMITTER_LOGIN, 'password' => $password, 'created' => false];
        }

        $host = wp_parse_url(home_url(), PHP_URL_HOST) ?: 'example.test';
        $user_id = wp_insert_user([
            'user_login'   => self::DEMO_SUBMITTER_LOGIN,
            'user_pass'    => $password,
            'user_email'   => 'edpq-demo-submitter@' . $host,
            'display_name' => 'EDPQ Demo Submitter',
            'role'         => 'net_submission_role',
        ]);
        if (is_wp_error($user_id)) {
            return ['login' => '', 'password' => '', 'created' => false];
        }

        // Flag the account so deletion never touches a normal user of the same login.
        update_user_meta($user_id, '_edpq_demo_user', 1);
        return ['login' => self::DEMO_SUBMITTER_LOGIN, 'password' => $password, 'created' => true];
    }

    /**
     * Delete the reserved demo Net Submitter test account if present.
     */
    public function delete_demo_submitter(): bool {
        $user = get_user_by('login', self::DEMO_SUBMITTER_LOGIN);
        if (!$user || !get_user_meta($user->ID, '_edpq_demo_user', true)) {
            return false;
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        return (bool) wp_delete_user($user->ID);
    }
}
