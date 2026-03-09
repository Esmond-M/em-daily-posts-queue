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
            return $decoded;
        }
        // Fall back to legacy serialize+base64
        $legacy = @unserialize(base64_decode($raw));
        return is_array($legacy) ? $legacy : [];
    }

    /**
     * Get the queue list from the database
     * @return array
     */
    public function get_queue_list_from_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'edpq_net_photos_queue_order';
        $row = $wpdb->get_row("SELECT list FROM $table_name WHERE id='1';", ARRAY_A);
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
        $placeholder_path = plugin_dir_path(dirname(__FILE__)) . 'assets/imgs/placeholder.png';
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
            if ($post_id && file_exists($placeholder_path)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                $upload = wp_upload_bits('placeholder-demo-' . $i . '.png', null, file_get_contents($placeholder_path));
                if (!$upload['error']) {
                    $filetype = wp_check_filetype($upload['file'], null);
                    $attachment = array(
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => 'Demo Placeholder',
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
}
