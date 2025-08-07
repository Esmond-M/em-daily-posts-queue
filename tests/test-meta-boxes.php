<?php

/**
 * Test meta boxes functionality
 */
class TestMetaBoxes extends WP_UnitTestCase
{
    private $meta_instance;
    private $test_post_id;

    /**
     * Set up the test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Include the class file
        require_once __DIR__ . '/../classes/cpt-meta-net-submissions.php';
        
        // Create an instance of the class for testing
        $this->meta_instance = new \EmDailyPostsQueue\init_plugin\Classes\cpt_meta_net_submission();
        
        // Create a test post
        $this->test_post_id = $this->factory->post->create([
            'post_type' => 'net_submission',
            'post_title' => 'Test Net Submission Post',
            'post_status' => 'publish'
        ]);
    }

    /**
     * Test meta box registration
     */
    public function test_meta_box_registration(): void
    {
        global $wp_meta_boxes;
        
        // Trigger the meta box registration
        $this->meta_instance->netSubmissionMetabox();
        
        // Check if meta box is registered
        $this->assertArrayHasKey(
            'net_submission',
            $wp_meta_boxes,
            'Meta box should be registered for net_submission post type.'
        );
        
        $this->assertArrayHasKey(
            'netSubmission_metaboxbox_id',
            $wp_meta_boxes['net_submission']['normal']['high'],
            'Meta box should have correct ID.'
        );
        
        $metabox = $wp_meta_boxes['net_submission']['normal']['high']['netSubmission_metaboxbox_id'];
        $this->assertEquals('Topic Custom Fields', $metabox['title'], 'Meta box should have correct title.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta box registered successfully.\033[0m\n");
    }

    /**
     * Test meta box save functionality with valid nonce
     */
    public function test_meta_box_save_with_valid_nonce(): void
    {
        // Set up POST data with valid nonce
        $_POST['topic_headline_value'] = 'Test Headline';
        $_POST['topic_caption_value'] = 'Test Caption';
        $_POST['net_submission_post_metabox_nonce'] = wp_create_nonce('net_submission_post_metabox');
        $_POST['post_type'] = 'net_submission';
        
        // Set current user with edit permissions
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        // Call the save function
        $result = $this->meta_instance->netSubmissionMetaboxSave($this->test_post_id);
        
        // Check if meta was saved
        $saved_headline = get_post_meta($this->test_post_id, 'topic_headline_value', true);
        $saved_caption = get_post_meta($this->test_post_id, 'topic_caption_value', true);
        
        $this->assertEquals('Test Headline', $saved_headline, 'Headline should be saved correctly.');
        $this->assertEquals('Test Caption', $saved_caption, 'Caption should be saved correctly.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta box save with valid nonce works.\033[0m\n");
    }

    /**
     * Test meta box save with invalid nonce
     */
    public function test_meta_box_save_with_invalid_nonce(): void
    {
        // Set up POST data with invalid nonce
        $_POST['topic_headline_value'] = 'Test Headline';
        $_POST['topic_caption_value'] = 'Test Caption';
        $_POST['net_submission_post_metabox_nonce'] = 'invalid_nonce';
        
        $result = $this->meta_instance->netSubmissionMetaboxSave($this->test_post_id);
        
        // Should return post ID without saving
        $this->assertEquals($this->test_post_id, $result, 'Should return post ID when nonce is invalid.');
        
        // Meta should not be saved
        $saved_headline = get_post_meta($this->test_post_id, 'topic_headline_value', true);
        $this->assertEmpty($saved_headline, 'Headline should not be saved with invalid nonce.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta box save with invalid nonce prevented.\033[0m\n");
    }

    /**
     * Test meta box save during autosave
     */
    public function test_meta_box_save_during_autosave(): void
    {
        // Define autosave constant
        define('DOING_AUTOSAVE', true);
        
        // Set up POST data
        $_POST['topic_headline_value'] = 'Test Headline';
        $_POST['net_submission_post_metabox_nonce'] = wp_create_nonce('net_submission_post_metabox');
        
        $result = $this->meta_instance->netSubmissionMetaboxSave($this->test_post_id);
        
        // Should return post ID during autosave
        $this->assertEquals($this->test_post_id, $result, 'Should return post ID during autosave.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta box save prevented during autosave.\033[0m\n");
    }

    /**
     * Test meta box save without permissions
     */
    public function test_meta_box_save_without_permissions(): void
    {
        // Set up POST data with valid nonce
        $_POST['topic_headline_value'] = 'Test Headline';
        $_POST['net_submission_post_metabox_nonce'] = wp_create_nonce('net_submission_post_metabox');
        $_POST['post_type'] = 'net_submission';
        
        // Set current user without edit permissions
        $user_id = $this->factory->user->create(['role' => 'subscriber']);
        wp_set_current_user($user_id);
        
        $result = $this->meta_instance->netSubmissionMetaboxSave($this->test_post_id);
        
        // Should return post ID without saving
        $this->assertEquals($this->test_post_id, $result, 'Should return post ID when user lacks permissions.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta box save prevented without permissions.\033[0m\n");
    }

    /**
     * Test meta values escaping
     */
    public function test_meta_values_escaping(): void
    {
        // Set up POST data with potentially dangerous content
        $_POST['topic_headline_value'] = '<script>alert("xss")</script>Test';
        $_POST['topic_caption_value'] = '<img src="x" onerror="alert(1)">Caption';
        $_POST['net_submission_post_metabox_nonce'] = wp_create_nonce('net_submission_post_metabox');
        $_POST['post_type'] = 'net_submission';
        
        // Set current user with edit permissions
        $user_id = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);
        
        // Call the save function
        $this->meta_instance->netSubmissionMetaboxSave($this->test_post_id);
        
        // Check if meta was properly escaped
        $saved_headline = get_post_meta($this->test_post_id, 'topic_headline_value', true);
        $saved_caption = get_post_meta($this->test_post_id, 'topic_caption_value', true);
        
        $this->assertStringNotContainsString('<script>', $saved_headline, 'Script tags should be escaped in headline.');
        $this->assertStringNotContainsString('onerror=', $saved_caption, 'Event handlers should be escaped in caption.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Meta values properly escaped.\033[0m\n");
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void
    {
        // Clean up POST data
        unset($_POST['topic_headline_value']);
        unset($_POST['topic_caption_value']);
        unset($_POST['net_submission_post_metabox_nonce']);
        unset($_POST['post_type']);
        
        // Clean up test post
        wp_delete_post($this->test_post_id, true);
        
        parent::tearDown();
    }
}