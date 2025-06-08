<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('cpt_net_submission')) {

    class cpt_net_submission
    {

        /** Declaring constructor
         */
        public function __construct()
        {

			add_action( 'init', [$this, 'em_daily_posts_register_cpts' ]  );
			add_action( 'init', [$this, 'net_submission_role' ]  );
			add_action( 'init', [$this, 'net_submission_cap' ]  );

	

        }

		public static function em_daily_posts_register_cpts() {

			/**
			 * Post Type: Net submissions.
			 */
		
			$labels = array(
				'name'          => esc_html__( 'Net submissions', 'net_submission' ),
				'singular_name' => esc_html__( 'Net submission', 'net_submission' ),
				'add_new'       => esc_html__( 'Add New Net submission', 'net_submission' ),
				'add_new_item'  => esc_html__( 'Add New Net submission', 'net_submission' ),
				'new_item'      => esc_html__( 'New Net submission', 'net_submission' ),
				'edit_item'     => esc_html__( 'Edit Net submission', 'net_submission' ),
				'view_item'     => esc_html__( 'View Net submission', 'net_submission' ),
				'all_items'     => esc_html__( 'All Net submissions', 'net_submission' ),
			);
		
			$args = array(
				'label'                 => esc_html__( 'Net submissions', 'net_submission' ),
				'labels'                => $labels,
				'description'           => '',
				'public'                => false,
				'publicly_queryable'    => false,
				'show_ui'               => true,
				'show_in_rest'          => true,
				'rest_base'             => '',
				'rest_controller_class' => 'WP_REST_Posts_Controller',
				'rest_namespace'        => 'wp/v2',
				'has_archive'           => 'net_submission',
				'show_in_menu'          => true,
				'show_in_nav_menus'     => true,
				'delete_with_user'      => false,
				'exclude_from_search'   => true,
				'capability_type'       => 'net_submission',
				'capabilities' => array(
					'edit_post' => 'edit_net_submission',
					'read_post' => 'read_net_submission',
					'delete_post' => 'delete_net_submission',
					'edit_posts' => 'edit_net_submissions',
					'edit_others_posts' => 'edit_others_net_submissions',
					'delete_posts' => 'delete_net_submissions',
					'publish_posts' => 'publish_net_submissions',
					'read_private_posts' => 'read_private_net_submissions',
					'read' => 'read',
					'delete_private_posts' => 'delete_private_net_submissions',
					'delete_published_posts' => 'delete_published_net_submissions',
					'delete_others_posts' => 'delete_others_net_submissions',
					'edit_private_posts' => 'edit_private_net_submissions',
					'edit_published_posts' => 'edit_published_net_submissions',
					'create_posts' => 'edit_net_submissions'
				),
				'map_meta_cap'          => true,
				'hierarchical'          => false,
				'can_export'            => true,
				'rewrite'               => array(
					'slug'       => 'net_submission',
					'with_front' => true,
				),
				'query_var'             => true,
				'supports'              => array( 'title',  'thumbnail'),
				//'taxonomies'            => array( 'category' ),
				'show_in_graphql'       => false,
			);
		
			register_post_type( 'net_submission', $args );
	
	}
	
    public function net_submission_role() {

        add_role('net_submission_role', 'Net Submitter', array(
            'read' => true,
            'create_posts' => false,
            'edit_posts' => false,
		    'edit_posts' => false,
            'edit_others_posts' => false,
            'publish_posts' => false,
            'manage_categories' => false,

        ));
		
    }
	
	public function net_submission_cap() {

		$net_submission_role = get_role( 'net_submission_role' );
        $admins = get_role( 'administrator' );

		$admins->add_cap( 'edit_net_submission' ); 
		$admins->add_cap( 'edit_net_submission' ); 
		$admins->add_cap( 'read_net_submission' ); 
		$admins->add_cap( 'delete_net_submission' ); 
		$admins->add_cap( 'edit_net_submissions' ); 
		$admins->add_cap( 'edit_others_net_submissions' ); 
		$admins->add_cap( 'delete_net_submissions' ); 
		$admins->add_cap( 'publish_net_submissions' ); 
		$admins->add_cap( 'read_private_net_submissions' ); 
		$admins->add_cap( 'delete_private_net_submissions' ); 
		$admins->add_cap( 'read_private_net_submissions' ); 
		$admins->add_cap( 'delete_published_net_submissions' ); 
		$admins->add_cap( 'delete_others_net_submissions' ); 
		$admins->add_cap( 'edit_private_net_submissions' ); 
		$admins->add_cap( 'edit_published_net_submissions' ); 

		$net_submission_role->add_cap( 'edit_net_submission' ); 
		$net_submission_role->add_cap( 'edit_net_submission' ); 
		$net_submission_role->add_cap( 'read_net_submission' ); 
		$net_submission_role->add_cap( 'delete_net_submission' ); 
		$net_submission_role->add_cap( 'edit_net_submissions' ); 
		$net_submission_role->add_cap( 'edit_others_net_submissions' ); 
		$net_submission_role->add_cap( 'delete_net_submissions' ); 
		$net_submission_role->add_cap( 'publish_net_submissions' ); 
		$net_submission_role->add_cap( 'read_private_net_submissions' ); 
		$net_submission_role->add_cap( 'delete_private_net_submissions' ); 
		$net_submission_role->add_cap( 'read_private_net_submissions' ); 
		$net_submission_role->add_cap( 'delete_published_net_submissions' ); 
		$net_submission_role->add_cap( 'delete_others_net_submissions' ); 
		$net_submission_role->add_cap( 'edit_private_net_submissions' ); 
		$net_submission_role->add_cap( 'edit_published_net_submissions' ); 

    }	

} // Closing bracket for classes

}

new cpt_net_submission;