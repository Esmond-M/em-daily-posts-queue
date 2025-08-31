<?php

/**
 * Test auto photo submissions functionality
 */
class TestAutoPhotoSubmissions extends WP_UnitTestCase
{
    private $auto_photo_instance;

    /**
     * Set up the test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
    // Include the class file
    require_once __DIR__ . '/../classes/class-photo-submission-queue-manager.php';
        
    // Create an instance of the class for testing
    $this->auto_photo_instance = new \EmDailyPostsQueue\init_plugin\Classes\PhotoNetSubmissionQueue();
    }

    /**
     * Test post type detection for net submission

    public function test_net_submission_skip_trash(): void
    {
        // Create a test post of type net_submission
        $post_id = $this->factory->post->create([
            'post_type' => 'net_submission',
            'post_title' => 'Test Net Submission',
            'post_status' => 'publish'
        ]);

        // Verify post exists
        $post = get_post($post_id);
        $this->assertNotNull($post, 'Test post should be created.');
        $this->assertEquals('net_submission', $post->post_type, 'Post type should be net_submission.');

        fwrite(STDOUT, "\n\033[32mSUCCESS: Net submission post type handling works.\033[0m\n");
    }
     */
    /**
     * Test multidimensional array comparison (duplicate from cron class)
     */
    public function test_auto_photo_array_comparison(): void
    {
        $array1 = [
            'postid' => 123,
            'queueNumber' => 1,
            'metadata' => [
                'title' => 'Test Title',
                'author' => 'Test Author'
            ]
        ];

        $array2 = [
            'postid' => 123,
            'queueNumber' => 1,
            'metadata' => [
                'title' => 'Test Title',
                'author' => 'Test Author'
            ]
        ];

        $result = $this->auto_photo_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertEmpty($result, 'Identical queue arrays should return empty result.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Auto photo array comparison works correctly.\033[0m\n");
    }

    /**
     * Test array comparison with queue differences
     */
    public function test_queue_array_differences(): void
    {
        $array1 = [
            'postid' => 123,
            'queueNumber' => 1
        ];

        $array2 = [
            'postid' => 123,
            'queueNumber' => 2
        ];

        $result = $this->auto_photo_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertNotEmpty($result, 'Different queue numbers should be detected.');
        $this->assertEquals(1, $result['queueNumber'], 'Queue number difference should be captured.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Queue array differences detected correctly.\033[0m\n");
    }

    /**
     * Test exception handling for invalid array input
     */
    public function test_invalid_array_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$array1 must be an array!');
        
        $this->auto_photo_instance->edpqcompareMultiDimensional('invalid', []);
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Exception thrown for invalid array input.\033[0m\n");
    }

    /**
     * Test post type validation for intercept function
     */
    public function test_intercept_publish_to_draft_validation(): void
    {
        // Create a test post of different type
        $regular_post_id = $this->factory->post->create([
            'post_type' => 'post',
            'post_title' => 'Regular Post',
            'post_status' => 'publish'
        ]);

        // Create mock data array
        $post_data = [
            'post_status' => 'draft',
            'post_title' => 'Test Title'
        ];

        // Should return early for non-net_submission post types
        // This tests the early return logic
        $result = $this->auto_photo_instance->intercept_publishToDraft($regular_post_id, $post_data);
        $this->assertNull($result, 'Function should return early for non-net_submission posts.');

        fwrite(STDOUT, "\n\033[32mSUCCESS: Post type validation works correctly.\033[0m\n");
    }

    /**
     * Test array handling with missing keys
     */
    public function test_array_missing_keys(): void
    {
        $array1 = [
            'key1' => 'value1',
            'key2' => 'value2',
            'unique_key' => 'unique_value'
        ];

        $array2 = [
            'key1' => 'value1',
            'key2' => 'value2'
            // missing 'unique_key'
        ];

        $result = $this->auto_photo_instance->edpqcompareMultiDimensional($array1, $array2);
        
        $this->assertNotEmpty($result, 'Missing keys should be detected.');
        $this->assertEquals('unique_value', $result['unique_key'], 'Missing key value should be in result.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Missing array keys handled correctly.\033[0m\n");
    }

    /**
     * Test strict mode comparison
     */
    public function test_strict_mode_comparison(): void
    {
        $array1 = ['number' => '123'];  // string
        $array2 = ['number' => 123];    // integer

        // Strict mode should detect type difference
        $result_strict = $this->auto_photo_instance->edpqcompareMultiDimensional($array1, $array2, true);
        $this->assertNotEmpty($result_strict, 'Strict mode should detect type differences.');

        // Non-strict mode should consider them equal
        $result_loose = $this->auto_photo_instance->edpqcompareMultiDimensional($array1, $array2, false);
        $this->assertEmpty($result_loose, 'Non-strict mode should consider string/int equal.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Strict and loose comparison modes work correctly.\033[0m\n");
    }
}