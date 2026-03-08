<?php
/**
 * Staff Custom Post Type
 *
 * Registers the `staff` CPT used for team member profiles displayed on
 * the Our People page.
 *
 * Each post uses:
 *   - Post title     → member's full name (including credentials)
 *   - Post editor    → optional bio / extended content
 *   - Featured image → headshot / profile photo
 *   - ACF fields     → job_title, phone, email  (group_staff)
 *
 * @package PC4S
 */

namespace PC4S\Classes\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Staff {

	/**
	 * CPT slug.
	 */
	const POST_TYPE = 'staff';

	/**
	 * @var Staff|null
	 */
	private static ?Staff $instance = null;

	public static function get_instance(): Staff {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init',    [ $this, 'register_post_type' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns',        [ $this, 'set_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_action( 'admin_head',                                          [ $this, 'column_styles' ] );
	}

	/**
	 * Define the admin list-table columns.
	 * Replaces the default "Title" with a thumbnail, a "Name" column, and a "Job Title" column.
	 *
	 * @param  array<string,string> $columns
	 * @return array<string,string>
	 */
	public function set_columns( array $columns ): array {
		$new = [];

		// Checkbox first.
		if ( isset( $columns['cb'] ) ) {
			$new['cb'] = $columns['cb'];
		}

		// Photo thumbnail before Name.
		$new['staff_photo']     = __( 'Photo', 'pc4s' );
		$new['title']           = __( 'Name', 'pc4s' );
		$new['staff_job_title'] = __( 'Job Title', 'pc4s' );
		$new['staff_email']     = __( 'Email', 'pc4s' );

		// Preserve any remaining default columns (date, etc.) except title (already added above).
		foreach ( $columns as $key => $label ) {
			if ( ! isset( $new[ $key ] ) ) {
				$new[ $key ] = $label;
			}
		}

		return $new;
	}

	/**
	 * Render content for each custom column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Current post ID.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'staff_photo':
				if ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, [ 50, 50 ], [ 'style' => 'border-radius:3px;display:block;' ] );
				} else {
					echo '<span aria-hidden="true" style="color:#c3c4c7;font-size:32px;line-height:50px;display:block;text-align:center;">&#128100;</span>';
				}
				break;

			case 'staff_job_title':
				$job_title = function_exists( 'get_field' ) ? (string) get_field( 'staff_job_title', $post_id ) : '';
				echo $job_title ? esc_html( $job_title ) : '<span aria-hidden="true">&mdash;</span>';
				break;

			case 'staff_email':
				$email = function_exists( 'get_field' ) ? sanitize_email( (string) get_field( 'staff_email', $post_id ) ) : '';
				echo $email
					? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>'
					: '<span aria-hidden="true">&mdash;</span>';
				break;
		}
	}

	/**
	 * Inline styles to size the Photo column in the admin list table.
	 * Scoped to the staff post-type screen only.
	 */
	public function column_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}
		echo '<style>
			.column-staff_photo { width: 62px; }
			.column-staff_photo img { width: 50px; height: 50px; object-fit: cover; }
		</style>';
	}

	/**
	 * Register the staff post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Staff', 'post type general name', 'pc4s' ),
			'singular_name'         => _x( 'Staff Member', 'post type singular name', 'pc4s' ),
			'menu_name'             => _x( 'Staff', 'admin menu', 'pc4s' ),
			'name_admin_bar'        => _x( 'Staff Member', 'add new on admin bar', 'pc4s' ),
			'add_new'               => __( 'Add New Staff', 'pc4s' ),
			'add_new_item'          => __( 'Add New Staff Member', 'pc4s' ),
			'new_item'              => __( 'New Staff Member', 'pc4s' ),
			'edit_item'             => __( 'Edit Staff Member', 'pc4s' ),
			'view_item'             => __( 'View Staff Member', 'pc4s' ),
			'all_items'             => __( 'All Staff', 'pc4s' ),
			'search_items'          => __( 'Search Staff', 'pc4s' ),
			'not_found'             => __( 'No staff members found.', 'pc4s' ),
			'not_found_in_trash'    => __( 'No staff members found in Trash.', 'pc4s' ),
			'featured_image'        => __( 'Staff Photo', 'pc4s' ),
			'set_featured_image'    => __( 'Set staff photo', 'pc4s' ),
			'remove_featured_image' => __( 'Remove staff photo', 'pc4s' ),
			'use_featured_image'    => __( 'Use as staff photo', 'pc4s' ),
			'archives'              => __( 'Staff Archives', 'pc4s' ),
			'filter_items_list'     => __( 'Filter staff list', 'pc4s' ),
			'items_list_navigation' => __( 'Staff list navigation', 'pc4s' ),
			'items_list'            => __( 'Staff list', 'pc4s' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
				'query_var'          => false,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => [ 'title', 'thumbnail', 'page-attributes' ],
			]
		);
	}
}
