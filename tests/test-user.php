<?php

/**
 * Test user functionality
 */
class TestUser extends WP_UnitTestCase
{
    private $user_id;

    /**
    * Set up the test environment, creating a user.
    */
    public function setUp(): void
    {
        parent::setUp();


        add_role('net_submission_role', 'Net Submitter', array( // create custom role
            'read' => true,
            'create_posts' => false,
            'edit_posts' => false,
		    'edit_posts' => false,
            'edit_others_posts' => false,
            'publish_posts' => false,
            'manage_categories' => false,

        ));

        $net_submission_role = get_role( 'net_submission_role' );
        $net_submission_role->add_cap( 'edit_net_submission' );  // add cap to role

        $user_data = [
            'role'         => 'net_submission_role',
            'user_login'   => 'exampleNetUser',
            'user_pass'    => 'passwordabc',
            'user_email'   => 'test@example.com',
        ];

        //check if user exists
        $user = get_user_by('login', $user_data['user_login']);

        if ($user) {
            $this->user_id = $user->ID;
            //error log
            fwrite(STDOUT, "\n\033[32mUser already exists: " . $this->user_id . "\033[0m\n");
            return;
        }

        $user_id_or_error = wp_insert_user($user_data);

        if (is_wp_error($user_id_or_error)) {
            // Handle error, log it, or fail the test if necessary
            error_log($user_id_or_error->get_error_message());
            $this->fail('User creation failed: ' . $user_id_or_error->get_error_message());
        } else {
            $this->user_id = $user_id_or_error;
            //log
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
            // Output success message in green
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
            // Output success message in green
            fwrite(STDOUT, "\n\033[32mSUCCESS: User role is net_submission_role.\033[0m\n");
            //log the user
            error_log('UserTest: ' . print_r($user));
        }
    }

    /**
    * Test if the net_submission_role can edit net_submission post type.
    */
    public function test_user_with_net_submission_role_can_edit_net_submission_posts() {
    $user_id = self::factory()->user->create( array(
        'role' => 'net_submission_role',
    ) );
 
    $this->assertTrue( user_can( $user_id, 'edit_net_submission' ) );
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
