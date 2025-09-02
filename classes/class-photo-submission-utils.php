<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

class PhotoNetSubmissionUtils {
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
}
