<?php
/**
 * Our Plugin class.
 * This handles all our hooks and stuff.
 *
 * @package stylepress
 */

namespace StylePress;

defined( 'STYLEPRESS_VERSION' ) || exit;

/**
 * All the magic happens here.
 *
 * Class Styles
 */
class Styles extends Base {

	const CPT = STYLEPRESS_SLUG . '-style';

	/**
	 * Initializes the plugin and sets all required filters.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'register_custom_post_type' ) );

	}

	/**
	 * Here is our magical custom post type that stores all our Elementor site wide styles.
	 *
	 * @since 2.0.0
	 */
	public function register_custom_post_type() {

		$labels = array(
			'name'               => 'Styles',
			'singular_name'      => 'Style',
			'menu_name'          => 'Styles',
			'parent_item_colon'  => 'Parent Style:',
			'all_items'          => 'All Styles',
			'view_item'          => 'View Style',
			'add_new_item'       => 'Add New Style',
			'add_new'            => 'New Style',
			'edit_item'          => 'Edit Style',
			'update_item'        => 'Update Style',
			'search_items'       => 'Search Styles',
			'not_found'          => 'No Styles found',
			'not_found_in_trash' => 'No Styles found in Trash',
		);

		$args = array(
			'description'         => 'Styles',
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'elementor', 'page-attributes', 'revisions' ),
			'taxonomies'          => array(),
			'hierarchical'        => true,
			'public'              => true,
			'show_in_menu'        => STYLEPRESS_DEBUG_OUTPUT ? true : false,
			'show_in_nav_menus'   => true,
			'exclude_from_search' => true,
			'menu_position'       => 36,
			'menu_icon'           => 'dashicons-star-filled',
			'can_export'          => true,
			'has_archive'         => false,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		);

		register_post_type( self::CPT, $args );

		register_taxonomy(
			STYLEPRESS_SLUG . '-cat',
			self::CPT,
			array(
				'hierarchical' => false,
				'label'        => 'Category',
			)
		);

	}

	public function get_categories() {
		$stylepress_categories   = [];
		$stylepress_categories[] = [
			'order'       => 10,
			'slug'        => 'header',
			'title'       => 'Header',
			'plural'      => 'Headers',
			'description' => 'These show at ..',
		];
		$stylepress_categories[] = [
			'order'       => 20,
			'slug'        => 'hero',
			'title'       => 'Hero',
			'plural'      => 'Heros',
			'description' => 'These show at ..',
		];
		$stylepress_categories[] = [
			'order'       => 30,
			'slug'        => 'content',
			'title'       => 'Content',
			'plural'      => 'Content Area',
			'inner'       => true,
			'description' => 'These show at ..',
		];
		$stylepress_categories[] = [
			'order'       => 40,
			'slug'        => 'footer',
			'title'       => 'Footer',
			'plural'      => 'Footers',
			'description' => 'These show at ..',
		];

		return apply_filters( 'stylepress_categories', $stylepress_categories );
	}

	public function get_all_styles( $category_slug = false, $include_empty = false ) {
		$styles = array();
		$args   = array(
			'post_type'           => self::CPT,
			'post_status'         => 'publish',
			'posts_per_page'      => - 1,
			'ignore_sticky_posts' => 1,
			'suppress_filters'    => false,
			'order'               => 'ASC',
			'orderby'             => 'title',
			'post_parent'         => 0,
		);
		if ( $category_slug ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => STYLEPRESS_SLUG . '-cat',
					'field'    => 'slug',
					'terms'    => $category_slug,
				)
			);
		}
		$posts_array = get_posts( $args );
		foreach ( $posts_array as $style ) {
			$styles[ $style->ID ] = $style->post_title;
		}

		if ( $include_empty ) {
			$styles = [ - 1 => '(Blank)' ] + $styles;
		}

		return $styles;
	}


	/**
	 * This lets us query what the currently selected page template is for a particular post ID
	 * We use the other function to get the defaults for non-page-ID posts (like archive etc..)
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Current post ID we're querying.
	 *
	 * @return bool
	 */
	public function get_page_styles( $post_id ) {
		$current_option = get_post_meta( $post_id, 'stylepress_style', true );

		if ( ! is_array( $current_option ) ) {
			$current_option = [];
		}

		return $current_option;
	}

	/**
	 * Works out what template is currently selected for the current page/post/archive/search/404 etc.
	 * Copied from my Widget Area Manager plugin
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	public function get_default_styles() {

		$style_settings = Settings::get_instance()->get( 'stylepress_styles' );
		if ( ! $style_settings || ! is_array( $style_settings ) ) {
			$style_settings = [];
		}

		$categories = Styles::get_instance()->get_categories();
		foreach ( $categories as $category ) {
			if ( ! isset( $style_settings[ $category['slug'] ] ) ) {
				$style_settings[ $category['slug'] ] = false;
			}
		}

		return $style_settings;

	}


	// Todo: we need this for categories and 404 etc..
	public function is_stylpress_enabled( $post ) {
		if ( $post && $post->ID ) {
			$template = get_post_meta( $post->ID, '_wp_page_template', true );
			if ( $template ) {
				return [
					'enabled' => false,
					'reason'  => 'StylePress disabled due to a custom template assigned to this page. Please remove the custom page template if you wish to use StylePress on this page.',
				];
			}
		}

		return [
			'enabled' => true,
		];
	}


	public function something() {

		global $post;

		if ( ! $ignore_override ) {

			if ( is_home() || is_front_page() ) {
				if ( 'page' == get_option( 'show_on_front' ) ) {
					$home_page_id = false;
					if ( is_front_page() ) {
						$home_page_id = get_option( 'page_on_front' );
					} else {
						$home_page_id = get_option( 'page_for_posts' );
					}
					if ( $home_page_id ) {
						$style = (int) $this->get_page_template( $home_page_id );
						if ( STYLEPRESS_OUTER_USE_THEME === $style ) {
							return $style; // Use theme by default.
						} else if ( $style > 0 ) {
							return apply_filters( 'stylepress_current_style', $style );
						}
					}
				}
			}
			if ( is_single() || is_page() || is_attachment() ) {
				// see if we have a custom style applied
				if ( $post && $post->ID ) {
					$style = (int) $this->get_page_template( $post->ID );
					if ( STYLEPRESS_OUTER_USE_THEME === $style ) {
						return $style; // Use theme by default.
					} else if ( $style > 0 ) {
						return apply_filters( 'stylepress_current_style', $style );
					}
				}
			}
		}

		// check for defaults for this page type
		$page_type = Plugin::get_instance()->get_current_page_type();
		if ( $page_type && ! empty( $style_settings['defaults'][ $page_type ] ) ) {
			return apply_filters( 'stylepress_current_style', $style_settings['defaults'][ $page_type ] );
		}
		// otherwise check for site wide default:
		if ( ! empty( $style_settings['defaults']['_global'] ) ) {
			return apply_filters( 'stylepress_current_style', $style_settings['defaults']['_global'] );
		}

		// otherwise return nothing, so we fallback to default standard theme
		return false;

	}

}

