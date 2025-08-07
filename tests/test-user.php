<?php

/**
 * Test user functionality
 */
class TestUser extends WP_UnitTestCase
{
    private $user_id;

    /**
    * Set up the test environment, creating a user and registering the custom post type.
    */
    public function setUp(): void
    {
        parent::setUp();

        // Register custom post type with proper capabilities
        if (!post_type_exists('net_submission')) {
            register_post_type('net_submission', [
                'public' => true,
                'label'  => 'Net Submission',
                'supports' => ['title', 'editor'],
                'capability_type' => 'net_submission',
                'map_meta_cap' => true,
            ]);
        }

        // Create custom role
        add_role('net_submission_role', 'Net Submitter', array(
            'read' => true,
        ));

        // Add all relevant capabilities to the role
        $net_submission_role = get_role('net_submission_role');
        if ($net_submission_role) {
            $net_submission_role->add_cap('edit_net_submission');
            $net_submission_role->add_cap('edit_net_submissions');
            $net_submission_role->add_cap('edit_others_net_submissions');
            $net_submission_role->add_cap('publish_net_submissions');
            $net_submission_role->add_cap('read_net_submission');
            $net_submission_role->add_cap('delete_net_submission');
        }

        $user_data = [
            'role'         => 'net_submission_role',
            'user_login'   => 'exampleNetUser',
            'user_pass'    => 'passwordabc',
            'user_email'   => 'test@example.com',
        ];

        // Check if user exists
        $user = get_user_by('login', $user_data['user_login']);

        if ($user) {
            $this->user_id = $user->ID;
            fwrite(STDOUT, "\n\033[32mUser already exists: " . $this->user_id . "\033[0m\n");
            return;
        }

        $user_id_or_error = wp_insert_user($user_data);

        if (is_wp_error($user_id_or_error)) {
            error_log($user_id_or_error->get_error_message());
            $this->fail('User creation failed: ' . $user_id_or_error->get_error_message());
        } else {
            $this->user_id = $user_id_or_error;
            fwrite(STDOUT, "\n\033[32mUser created: " . $this->user_id . "\033[0m\n");
        }
    }

    /**
    * Test if the created user's email is valid.
    */
    public function test_user_email(): void
    {
        $user = get_user_by('id', $this->user_id);
        if (!$user) {
            $this->fail('Failed to retrieve user by ID.');
        } else {
            $this->assertEquals('test@example.com', $user->user_email, 'User email is valid.');
            fwrite(STDOUT, "\n\033[32mSUCCESS: User email is valid.\033[0m\n");
        }
    }

    /**
    * Test if the created user has the net_submission_role role.
    */
    public function test_user_role(): void
    {
        $user = get_user_by('id', $this->user_id);
        if (!$user) {
            $this->fail('Failed to retrieve user by ID.');
        } else {
            $this->assertContains('net_submission_role', $user->roles, 'User role is net_submission_role.');
            fwrite(STDOUT, "\n\033[32mSUCCESS: User role is net_submission_role.\033[0m\n");
            error_log('UserTest: ' . print_r($user, true));
        }
    }

    /**
    * Test if the net_submission_role can edit net_submission post type.
    */
    public function test_user_with_net_submission_role_can_edit_net_submission_posts()
    {
        $user_id = self::factory()->user->create([
            'role' => 'net_submission_role',
        ]);
        // Test plural capability for editing posts
        $this->assertTrue(user_can($user_id, 'edit_net_submissions'), 'User should be able to edit net_submission posts.');
    }

    /**
    * Clean up the test environment after tests.
    */
    public function tearDown(): void
    {
        wp_delete_user($this->user_id);
        parent::tearDown();
    }
}
