<?php

/**
 * Test custom post type net submissions functionality
 */
class TestCptNetSubmissions extends WP_UnitTestCase
{
    private $cpt_instance;

    /**
     * Set up the test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Include the class file
        require_once __DIR__ . '/../classes/cpt-net-submissions.php';
        
        // Create an instance of the class for testing
        $this->cpt_instance = new \EmDailyPostsQueue\init_plugin\Classes\cpt_net_submission();
    }

    /**
     * Test if custom post type is registered successfully
     */
    public function test_cpt_registration_success(): void
    {
        // Call the method that registers the CPT
        $this->cpt_instance::em_daily_posts_register_cpts();
        
        // Check if the post type exists
        $post_type_exists = post_type_exists('net_submission');
        $this->assertTrue($post_type_exists, 'Net submission custom post type should be registered.');
        
        // Check post type object properties
        $post_type_object = get_post_type_object('net_submission');
        $this->assertNotNull($post_type_object, 'Post type object should exist.');
        $this->assertEquals('Net submissions', $post_type_object->labels->name);
        $this->assertEquals('net_submission', $post_type_object->capability_type);
        $this->assertFalse($post_type_object->public);
        $this->assertTrue($post_type_object->show_ui);
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Custom post type registered successfully.\033[0m\n");
    }

    /**
     * Test if net submission role is created successfully
     */
    public function test_role_creation_success(): void
    {
        // Call the method that creates the role
        $this->cpt_instance->net_submission_role();
        
        // Check if the role exists
        $role = get_role('net_submission_role');
        $this->assertNotNull($role, 'Net submission role should be created.');
        $this->assertTrue($role->has_cap('read'), 'Net submission role should have read capability.');
        $this->assertFalse($role->has_cap('edit_posts'), 'Net submission role should not have edit_posts capability.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Net submission role created successfully.\033[0m\n");
    }

    /**
     * Test if capabilities are assigned correctly
     */
    public function test_capability_assignment_success(): void
    {
        // Ensure roles exist first
        $this->cpt_instance->net_submission_role();
        
        // Call the method that assigns capabilities
        $this->cpt_instance->net_submission_cap();
        
        // Check admin capabilities
        $admin_role = get_role('administrator');
        $this->assertTrue($admin_role->has_cap('edit_net_submission'), 'Admin should have edit_net_submission capability.');
        $this->assertTrue($admin_role->has_cap('read_net_submission'), 'Admin should have read_net_submission capability.');
        
        // Check net submission role capabilities
        $net_role = get_role('net_submission_role');
        $this->assertTrue($net_role->has_cap('edit_net_submission'), 'Net submission role should have edit_net_submission capability.');
        $this->assertTrue($net_role->has_cap('upload_files'), 'Net submission role should have upload_files capability.');
        
        fwrite(STDOUT, "\n\033[32mSUCCESS: Capabilities assigned successfully.\033[0m\n");
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void
    {
        // Clean up any created roles
        remove_role('net_submission_role');
        
        // Remove admin capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('edit_net_submission');
            $admin_role->remove_cap('read_net_submission');
            $admin_role->remove_cap('delete_net_submission');
            $admin_role->remove_cap('edit_net_submissions');
            $admin_role->remove_cap('edit_others_net_submissions');
            $admin_role->remove_cap('delete_net_submissions');
            $admin_role->remove_cap('publish_net_submissions');
            $admin_role->remove_cap('read_private_net_submissions');
            $admin_role->remove_cap('delete_private_net_submissions');
            $admin_role->remove_cap('delete_published_net_submissions');
            $admin_role->remove_cap('delete_others_net_submissions');
            $admin_role->remove_cap('edit_private_net_submissions');
            $admin_role->remove_cap('edit_published_net_submissions');
        }
        
        parent::tearDown();
    }
}