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

        }

		public static function em_daily_posts_register_cpts() {

			/**
			 * Post Type: portfolio.
			 */
		
			$labels = array(
				'name'          => esc_html__( ' Net submissions', 'net_submission' ),
				'singular_name' => esc_html__( 'net_submission', 'net_submission' ),
			);
		
			$args = array(
				'label'                 => esc_html__( 'net_submission', 'net_submission' ),
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
				'capability_type'       => 'post',
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

} // Closing bracket for classes

}

new cpt_net_submission;