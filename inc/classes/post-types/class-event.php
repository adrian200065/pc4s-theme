<?php
/**
 * Event Custom Post Type
 *
 * Registers the `pc4s_event` CPT and `event_type` taxonomy.
 * All registration is hooked to `init`.
 *
 * @package PC4S
 */

namespace PC4S\Classes\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Event {

	/**
	 * CPT slug.
	 */
	const POST_TYPE = 'pc4s_event';

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'event_type';

	/**
	 * @var Event|null
	 */
	private static ?Event $instance = null;

	public static function get_instance(): Event {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Meta key used by EventQuery for pre-computed next occurrence dates.
	 */
	const NEXT_OCC_META = '_event_next_occurrence';

	private function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'seed_taxonomy_terms' ] );
		add_action( 'pre_get_posts', [ $this, 'filter_archive_query' ] );
	}

	/**
	 * Register the pc4s_event post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Events', 'post type general name', 'pc4s' ),
			'singular_name'         => _x( 'Event', 'post type singular name', 'pc4s' ),
			'menu_name'             => _x( 'Events', 'admin menu', 'pc4s' ),
			'name_admin_bar'        => _x( 'Event', 'add new on admin bar', 'pc4s' ),
			'add_new'               => __( 'Add New', 'pc4s' ),
			'add_new_item'          => __( 'Add New Event', 'pc4s' ),
			'new_item'              => __( 'New Event', 'pc4s' ),
			'edit_item'             => __( 'Edit Event', 'pc4s' ),
			'view_item'             => __( 'View Event', 'pc4s' ),
			'all_items'             => __( 'All Events', 'pc4s' ),
			'search_items'          => __( 'Search Events', 'pc4s' ),
			'parent_item_colon'     => __( 'Parent Events:', 'pc4s' ),
			'not_found'             => __( 'No events found.', 'pc4s' ),
			'not_found_in_trash'    => __( 'No events found in Trash.', 'pc4s' ),
			'featured_image'        => __( 'Event Image', 'pc4s' ),
			'set_featured_image'    => __( 'Set event image', 'pc4s' ),
			'remove_featured_image' => __( 'Remove event image', 'pc4s' ),
			'use_featured_image'    => __( 'Use as event image', 'pc4s' ),
			'archives'              => __( 'Event Archives', 'pc4s' ),
			'insert_into_item'      => __( 'Insert into event', 'pc4s' ),
			'uploaded_to_this_item' => __( 'Uploaded to this event', 'pc4s' ),
			'filter_items_list'     => __( 'Filter events list', 'pc4s' ),
			'items_list_navigation' => __( 'Events list navigation', 'pc4s' ),
			'items_list'            => __( 'Events list', 'pc4s' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false, // Placed under PC4S top-level menu.
				'show_in_nav_menus'  => true,  // Exposes the CPT archive in Appearance → Menus.
				'show_in_rest'       => false,
				'query_var'          => true,
				'rewrite'            => [ 'slug' => 'events' ],
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => [ 'title', 'thumbnail' ],
				'taxonomies'         => [ self::TAXONOMY ],
			]
		);
	}

	/**
	 * Register the event_type taxonomy.
	 */
	public function register_taxonomy(): void {
		$labels = [
			'name'              => _x( 'Event Types', 'taxonomy general name', 'pc4s' ),
			'singular_name'     => _x( 'Event Type', 'taxonomy singular name', 'pc4s' ),
			'search_items'      => __( 'Search Event Types', 'pc4s' ),
			'all_items'         => __( 'All Event Types', 'pc4s' ),
			'parent_item'       => __( 'Parent Event Type', 'pc4s' ),
			'parent_item_colon' => __( 'Parent Event Type:', 'pc4s' ),
			'edit_item'         => __( 'Edit Event Type', 'pc4s' ),
			'update_item'       => __( 'Update Event Type', 'pc4s' ),
			'add_new_item'      => __( 'Add New Event Type', 'pc4s' ),
			'new_item_name'     => __( 'New Event Type Name', 'pc4s' ),
			'menu_name'         => __( 'Event Types', 'pc4s' ),
			'not_found'         => __( 'No event types found.', 'pc4s' ),
		];

		register_taxonomy(
			self::TAXONOMY,
			[ self::POST_TYPE ],
			[
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => false,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => 'event-type' ],
			]
		);
	}

	/**
	 * Customize the main WP_Query for the pc4s_event post-type archive.
	 *
	 * Orders events by their pre-computed next-occurrence date (ASC) and
	 * restricts the set to events that have at least one future occurrence.
	 * All complex query logic stays here — the template only renders.
	 *
	 * @param \WP_Query $query The current WP_Query instance.
	 */
	public function filter_archive_query( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( self::POST_TYPE ) ) {
			return;
		}

		$today = gmdate( 'Y-m-d' );

		$query->set( 'meta_key', self::NEXT_OCC_META );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', 50 );
		$query->set(
			'meta_query',
			[
				[
					'key'     => self::NEXT_OCC_META,
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				],
			]
		);
	}

	/**
	 * Ensure default taxonomy terms exist.
	 *
	 * Guarded by a weekly transient so the DB check only runs once per week
	 * rather than on every request.
	 */
	public function seed_taxonomy_terms(): void {
		$transient = 'pc4s_event_type_seeded';

		if ( get_transient( $transient ) ) {
			return;
		}

		$defaults = [
			'pc4s'      => 'PC4S',
			'true-blue' => 'True Blue Peers 4 Success',
		];

		foreach ( $defaults as $slug => $name ) {
			if ( ! term_exists( $slug, self::TAXONOMY ) ) {
				wp_insert_term( $name, self::TAXONOMY, [ 'slug' => $slug ] );
			}
		}

		set_transient( $transient, true, WEEK_IN_SECONDS );
	}
}
