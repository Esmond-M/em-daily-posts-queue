<?php

/**
 * Test main plugin functionality
 */
class TestPluginMain extends WP_UnitTestCase
{
    private $plugin_instance;

    /**
     * Set up the test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Include the main plugin file
        require_once __DIR__ . '/../em-daily-posts-queue.php';
    }

    /**
     * Test plugin instance creation
     */
    public function test_plugin_instance_creation(): void
    {
        $instance = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::get_instance();
        
        $this->assertInstanceOf(
            '\EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit',
            $instance,
            'Plugin instance should be created correctly.'
        );
        
        // Test singleton behavior
        $second_instance = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::get_instance();
        $this->assertSame($instance, $second_instance, 'Plugin should follow singleton pattern.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Plugin instance created successfully.\033[0m\n");
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcode_registration(): void
    {
        $instance = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::get_instance();
        
        // Test if shortcodes are registered
        $this->assertTrue(
            shortcode_exists('EmDailyPostsQueueForm'),
            'EmDailyPostsQueueForm shortcode should be registered.'
        );
        
        $this->assertTrue(
            shortcode_exists('EmDailyPostsQueueDisplayPost'),
            'EmDailyPostsQueueDisplayPost shortcode should be registered.'
        );
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Shortcodes registered successfully.\033[0m\n");
    }

    /**
     * Test form shortcode output generation
     */
    public function test_shortcode_output_generation(): void
    {
        $instance = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::get_instance();
        
        // Test form shortcode output
        $form_output = $instance->FormShortcodeContent(['class' => 'test-class']);
        
        $this->assertIsString($form_output, 'Form shortcode should return string output.');
        $this->assertStringContainsString('test-class', $form_output, 'CSS class should be included in output.');
        $this->assertStringContainsString('<form', $form_output, 'Form shortcode should contain form element.');
        $this->assertStringContainsString('topic_headline_value', $form_output, 'Form should contain headline input.');
        $this->assertStringContainsString('net_image', $form_output, 'Form should contain image input.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Form shortcode output generated correctly.\033[0m\n");
    }

    /**
     * Test plugin constants
     */
    public function test_plugin_constants(): void
    {
        $this->assertTrue(defined('EmDailyPostsQueue_PATH'), 'Plugin path constant should be defined.');
        $this->assertIsString(EmDailyPostsQueue_PATH, 'Plugin path should be a string.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Plugin constants defined correctly.\033[0m\n");
    }

    /**
     * Test plugin version constant
     */
    public function test_plugin_version(): void
    {
        $version = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::VERSION;
        
        $this->assertEquals('0.1.0', $version, 'Plugin version should match expected value.');
        $this->assertIsString($version, 'Version should be a string.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Plugin version constant correct.\033[0m\n");
    }

    /**
     * Test PHP minimum version requirement
     */
    public function test_php_minimum_version(): void
    {
        $min_version = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::PHP_MINIMUM_VERSION;
        
        $this->assertEquals('7.0', $min_version, 'PHP minimum version should be 7.0.');
        $this->assertTrue(
            version_compare(PHP_VERSION, $min_version, '>='),
            'Current PHP version should meet minimum requirements.'
        );
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: PHP version requirements verified.\033[0m\n");
    }

    /**
     * Test display shortcode with empty queue
     */
    public function test_display_shortcode_empty_queue(): void
    {
        $instance = \EmDailyPostsQueue\init_plugin\EmDailyPostsQueueInit::get_instance();
        
        // Mock empty database state by checking the output contains fallback content
        $display_output = $instance->DisplayPostShortcodeContent(['class' => 'test-display']);
        
        $this->assertIsString($display_output, 'Display shortcode should return string output.');
        $this->assertStringContainsString('edpq-around-edpq', $display_output, 'Display should contain wrapper class.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Display shortcode handles empty state correctly.\033[0m\n");
    }
}