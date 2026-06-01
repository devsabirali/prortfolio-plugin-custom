<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Powerfolio_Portfolio {

	/** @var int Post ID queued for featured-image resolution after save. */
	private static $pending_thumbnail_post_id = 0;

	public function __construct() {

		add_action( 'init', array( $this, 'register_portfolio_post_type') , 20 );
		add_action( 'init', array( $this, 'create_portfolio_taxonomies') , 20 );
		add_action( 'init', array( $this, 'register_portfolio_shortcodes') , 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts') , 20 );
		add_action( 'after_setup_theme', array( $this, 'ensure_thumbnail_support' ), 20 );
		add_filter( 'post_thumbnail_id', array( $this, 'filter_post_thumbnail_id' ), 10, 2 );
		add_filter( 'has_post_thumbnail', array( $this, 'filter_has_post_thumbnail' ), 10, 3 );
		add_action( 'save_post_elemenfolio', array( $this, 'maybe_set_featured_image_on_save' ), 20, 2 );
		add_action( 'elementor/frontend/widget/before_render', array( $this, 'fix_elementor_image_widget_placeholder' ), 10, 1 );
		
		//Flush rewrite rules
		add_action( 'init', array( __CLASS__, 'flush_rewrite_rules_maybe') , 20 );
	}

	/**
	 * Ensure themes expose featured images for portfolio items.
	 */
	public function ensure_thumbnail_support() {
		add_post_type_support( 'elemenfolio', 'thumbnail' );
		if ( function_exists( 'add_theme_support' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
	}

	/**
	 * Use portfolio fallbacks when no WordPress featured image is set.
	 *
	 * @param int          $thumbnail_id Attachment ID.
	 * @param int|WP_Post  $post         Post ID or object.
	 * @return int
	 */
	public function filter_post_thumbnail_id( $thumbnail_id, $post ) {
		if ( $thumbnail_id ) {
			return $thumbnail_id;
		}

		$post = get_post( $post );
		if ( ! $post || 'elemenfolio' !== $post->post_type ) {
			return $thumbnail_id;
		}

		$resolved = Powerfolio_Common_Settings::resolve_portfolio_thumbnail_id( $post->ID );
		return $resolved > 0 ? $resolved : $thumbnail_id;
	}

	/**
	 * @param bool        $has_thumbnail Whether the post has a featured image.
	 * @param int|WP_Post $post          Post ID or object.
	 * @param int         $thumbnail_id  Attachment ID.
	 * @return bool
	 */
	public function filter_has_post_thumbnail( $has_thumbnail, $post, $thumbnail_id ) {
		if ( $has_thumbnail ) {
			return $has_thumbnail;
		}

		$post = get_post( $post );
		if ( ! $post || 'elemenfolio' !== $post->post_type ) {
			return $has_thumbnail;
		}

		return Powerfolio_Common_Settings::resolve_portfolio_thumbnail_id( $post->ID ) > 0;
	}

	/**
	 * Persist resolved image as featured image when saving portfolio items.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function maybe_set_featured_image_on_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $post instanceof WP_Post || 'elemenfolio' !== $post->post_type ) {
			return;
		}

		// Run after REST/block editor has finished so the JSON response stays valid.
		self::$pending_thumbnail_post_id = (int) $post_id;
		add_action( 'shutdown', array( __CLASS__, 'process_pending_thumbnail' ), 5 );
	}

	/**
	 * Set featured image from Elementor/content fallback after the request completes.
	 */
	public static function process_pending_thumbnail() {
		$post_id = self::$pending_thumbnail_post_id;
		self::$pending_thumbnail_post_id = 0;

		if ( $post_id < 1 || get_post_thumbnail_id( $post_id ) ) {
			return;
		}

		$resolved = Powerfolio_Common_Settings::resolve_portfolio_thumbnail_id( $post_id );
		if ( $resolved > 0 ) {
			set_post_thumbnail( $post_id, $resolved );
		}
	}

	/**
	 * Replace Elementor placeholder images on single portfolio pages.
	 *
	 * @param \Elementor\Element_Base $element Elementor element.
	 */
	public function fix_elementor_image_widget_placeholder( $element ) {
		if ( ! is_singular( 'elemenfolio' ) || ! is_object( $element ) || ! method_exists( $element, 'get_name' ) ) {
			return;
		}

		if ( 'image' !== $element->get_name() || ! method_exists( $element, 'get_settings' ) ) {
			return;
		}

		$settings = $element->get_settings();
		$image    = isset( $settings['image'] ) && is_array( $settings['image'] ) ? $settings['image'] : array();
		$image_id = isset( $image['id'] ) ? (int) $image['id'] : 0;
		$image_url = isset( $image['url'] ) ? $image['url'] : '';

		if ( $image_id > 0 && ! Powerfolio_Common_Settings::is_placeholder_image_url( $image_url ) ) {
			return;
		}

		$post_id    = get_queried_object_id();
		$resolved_id = Powerfolio_Common_Settings::resolve_portfolio_thumbnail_id( $post_id );
		if ( $resolved_id <= 0 ) {
			return;
		}

		$resolved_url = wp_get_attachment_image_url( $resolved_id, 'full' );
		if ( ! $resolved_url ) {
			return;
		}

		$settings['image'] = array(
			'id'  => $resolved_id,
			'url' => $resolved_url,
		);

		if ( method_exists( $element, 'set_settings' ) ) {
			$element->set_settings( $settings );
		}
	}

	/*
	* Register Elemenfolio/Portfolio Post Type
	*/
	public function register_portfolio_post_type()	{
		$args = array();	

		// Filters
		$portfolio_cpt_slug_rewrite = apply_filters( 'elpt_portfolio_cpt_slug_rewrite', 'portfolio' ); 
		$portfolio_cpt_has_archive = apply_filters( 'elpt_portfolio_cpt_has_archive', false ); 
		$portfolio_cpt_name = apply_filters( 'elpt_portfolio_cpt_name', __( 'Portfolio', 'portfolio-elementor' ) ); 


		// Portfolio Post Type
		$args['post-type-portfolio'] = array(
			'labels' => array(
				'name' => $portfolio_cpt_name,
				'singular_name' => __( 'Item', 'portfolio-elementor' ),
				'add_new' => __( 'Add New Item', 'portfolio-elementor' ),
				'add_new_item' => __( 'Add New Item', 'portfolio-elementor' ),
				'edit_item' => __( 'Edit Item', 'portfolio-elementor' ),
				'new_item' => __( 'New Item', 'portfolio-elementor' ),
				'view_item' => __( 'View Item', 'portfolio-elementor' ),
				'search_items' => __( 'Search Through portfolio', 'portfolio-elementor' ),
				'not_found' => __( 'No items found', 'portfolio-elementor' ),
				'not_found_in_trash' => __( 'No items found in Trash', 'portfolio-elementor' ),
				'parent_item_colon' => __( 'Parent Item:', 'portfolio-elementor' ),
				'menu_name' => $portfolio_cpt_name,				
			),		  
			'hierarchical' => false,
	        'description' => __( 'Add a New Item', 'portfolio-elementor' ),
	        'menu_icon' =>  'dashicons-images-alt',
	        'public' => true,
	        'publicly_queryable' => true,
			'exclude_from_search' => false,
			'has_archive' => $portfolio_cpt_has_archive,
	        'query_var' => true,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'rewrite' => array(
				'slug'       => $portfolio_cpt_slug_rewrite,
				'with_front' => false,
			),
			'show_in_rest' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
	        // This is where we add taxonomies to our CPT
        	//'taxonomies'          => array( 'category' ),
		);	

		// Register post type: name, arguments
		register_post_type('elemenfolio', $args['post-type-portfolio']);
	}	

	/*
	* Register Taxonomies
	*/
	public function create_portfolio_taxonomies() {
		// Config
		$elemenfoliocategory_slug_rewrite = apply_filters( 'elpt_elemenfoliocategory_slug_rewrite', 'portfoliocategory' );

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Portfolio Categories', 'taxonomy general name', 'portfolio-elementor' ),
			'singular_name'     => _x( 'Portfolio Category', 'taxonomy singular name', 'portfolio-elementor' ),
			'search_items'      => __( 'Search Portfolio Categories', 'portfolio-elementor' ),
			'all_items'         => __( 'All Portfolio Categories', 'portfolio-elementor' ),
			'parent_item'       => __( 'Parent Portfolio Category', 'portfolio-elementor' ),
			'parent_item_colon' => __( 'Parent Portfolio Category:', 'portfolio-elementor' ),
			'edit_item'         => __( 'Edit Portfolio Category', 'portfolio-elementor' ),
			'update_item'       => __( 'Update Portfolio Category', 'portfolio-elementor' ),
			'add_new_item'      => __( 'Add New Portfolio Category', 'portfolio-elementor' ),
			'new_item_name'     => __( 'New Portfolio Category', 'portfolio-elementor' ),
			'menu_name'         => __( 'Portfolio Categories', 'portfolio-elementor' ),
		);
	
		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $elemenfoliocategory_slug_rewrite ),
			'show_in_rest' =>true,
		);
	
		register_taxonomy( 'elemenfoliocategory', array( 'elemenfolio' ), $args );
	}	

	/*
	* flush_rewrite_rules_maybe()
	*/
	public static function flush_rewrite_rules_maybe() {
		if ( get_option( 'elpt_flush_rewrite_rules_flag' ) ) {
			flush_rewrite_rules();
			delete_option( 'elpt_flush_rewrite_rules_flag' );
		}
	}

	//Enable Elementor on portfolio post type
	//From https://wordpress.org/support/topic/option-to-enable-by-default-elementor-for-custom-post-type/
	public static function add_cpt_support_for_elementor() {
		
		$cpt_support = get_option( 'elementor_cpt_support' );
		
		//check if option DOESN'T exist in db
		if( ! $cpt_support ) {
			$cpt_support = [ 'page', 'post', 'elemenfolio' ]; //create array of our default supported post types
			update_option( 'elementor_cpt_support', $cpt_support ); //write it to the database
		}
		
		//if it DOES exist, but portfolio is NOT defined
		else if( ! in_array( 'elemenfolio', $cpt_support ) ) {
			$cpt_support[] = 'elemenfolio'; //append to array
			update_option( 'elementor_cpt_support', $cpt_support ); //update database
		}
	}

	/*
	*  Enqueue scripts for shortcode
	*/
	public static function enqueue_scripts() {
		$assets_dir =  plugin_dir_url( __DIR__ );	

		////Isotope			
		wp_enqueue_script( 'jquery-isotope',  $assets_dir. 'vendor/isotope/js/isotope.pkgd.js', array('jquery', 'imagesloaded'), '20151215', true );
		wp_enqueue_script( 'jquery-packery', $assets_dir. 'vendor/isotope/js/packery-mode.pkgd.min.js', array('jquery', 'imagesloaded', 'jquery-isotope'), '20151215', true );

		//Image Lightbox
		if ( apply_filters( 'elpt-enable-simple-lightbox', true ) == true ) {
			wp_enqueue_script( 'simple-lightbox-js',  $assets_dir.  'vendor/simplelightbox/dist/simple-lightbox.min.js', array('jquery'), '2.14.2', true );
			wp_enqueue_style( 'simple-lightbox-css', $assets_dir .  'vendor/simplelightbox/dist/simplelightbox.min.css', array(), '2.14.2' );
			wp_enqueue_script( 'elpt-portfoliojs-lightbox', $assets_dir . 'assets/js/custom-portfolio-lightbox.js', array('jquery'), '3.2.2', true );
		}
		
		//Custom JS
		wp_enqueue_script( 'elpt-portfoliojs', $assets_dir . 'assets/js/custom-portfolio.js', array('jquery'), '3.2.2', true );

		//Custom CSS
		wp_enqueue_style( 'elpt-portfolio-css', $assets_dir .  'assets/css/powerfolio_css.css', array(), '3.2.2' );
		Powerfolio_Styles::enqueue_assets();
	}

	/*
	* get_widget_settings()
	*/
	public static function get_widget_settings($settings, $widget = 'portfolio') {

		switch ($widget) {
			case 'portfolio':
				
				extract(shortcode_atts(array(
					//"id" => '',
					"postsperpage" => '',
					"pagination" => '',
					"pagination_postsperpage" => '',
					"showfilter" => '',
					"taxonomy" => '',
					"type" => '',
					"style" => '',
					"columns" => '',
					"columns_mobile" => '',
					"margin" => '',
					"linkto" => '',
					"hover" => '',
					"zoom_effect" => '',
					"post_type" => '',
					"tax_text" => '',
					"showallbtn" => '',
					"hide_item_title" => '',
					"hide_item_category" => '',
					"taxonomy" => '',
					"grid_fixed_layout" => '',
					'element_id' => '',
					'item_icon' => array(),
				), $settings));


				// Set Default Values
				if ( $type == "yes"  ) {
					$type = true;
				}

				if ( $post_type == '' ) {
					$post_type = 'elemenfolio';
				}
		
				// Escape and validate the attributes
				$settings = array(
					'postsperpage'       => esc_attr($postsperpage),
					'pagination'       => esc_attr($pagination),
					'pagination_postsperpage' => esc_attr($pagination_postsperpage),
					'showfilter'         => esc_attr($showfilter),
					'taxonomy'           => esc_attr($taxonomy),
					'type'               => esc_attr($type),
					'style'              => esc_attr($style),
					'columns'            => esc_attr($columns),
					'columns_mobile'     => esc_attr($columns_mobile),
					'margin'             => esc_attr($margin),
					'linkto'             => esc_attr($linkto),
					'hover'              => esc_attr($hover),
					'zoom_effect'        => esc_attr($zoom_effect),
					'post_type'          => esc_attr($post_type),
					'tax_text'           => esc_attr($tax_text),
					'showallbtn'         => esc_attr($showallbtn),
					'hide_item_title'    => esc_attr($hide_item_title),
					'hide_item_category' => esc_attr($hide_item_category),
					'grid_fixed_layout'  => esc_attr($grid_fixed_layout),
					'element_id' => esc_attr($element_id),
					'item_icon'  => $item_icon,
				);
			
				break;

			case 'image_gallery':

				$settings = $settings;
				$settings['taxonomy'] = '';
				$settings['post_type'] = '';
				$settings['type'] = '';
				$settings['hide_item_category'] = '';
				$settings['hide_item_title'] = '';
				$settings['postsperpage'] = 99;
				$settings['linkto'] = 'image';
				$settings['zoom_effect'] = '';
				$settings['columns_mobile'] = '';

			break;

			case 'portfolio_elementor':
				// Settings passed directly from Elementor widget (not via shortcode)
				// This allows complex data like item_icon array to pass through

				// Set defaults for missing keys
				$defaults = array(
					'postsperpage' => '',
					'pagination' => '',
					'pagination_postsperpage' => '',
					'showfilter' => '',
					'taxonomy' => '',
					'type' => '',
					'style' => '',
					'columns' => '',
					'columns_mobile' => '',
					'margin' => '',
					'linkto' => '',
					'hover' => '',
					'zoom_effect' => '',
					'post_type' => 'elemenfolio',
					'tax_text' => '',
					'showallbtn' => '',
					'hide_item_title' => '',
					'hide_item_category' => '',
					'grid_fixed_layout' => '',
					'element_id' => '',
					'item_icon' => array(),
					'layout_preset' => '',
					'pagination_enable' => '',
					'pagination_mode' => '',
					'accent_color' => '',
					'tab_radius' => '',
					'card_radius' => '',
				);

				// Merge with defaults
				$settings = array_merge($defaults, $settings);

				// Apply escaping to scalar string values for security
				// Skip arrays like 'item_icon' and 'taxonomy' (when array)
				$skip_escape = array( 'item_icon', 'taxonomy', 'type', 'layout_preset', 'pagination_mode', 'accent_color', 'tab_radius', 'card_radius', 'pagination_enable' );
				foreach ($settings as $key => $value) {
					if (is_string($value) && !in_array($key, $skip_escape, true)) {
						$settings[$key] = esc_attr($value);
					}
				}

				// Set default post type if empty
				if (empty($settings['post_type'])) {
					$settings['post_type'] = 'elemenfolio';
				}

				// Convert 'type' value (use loose comparison for consistency with 'portfolio' case)
				if ($settings['type'] == 'yes') {
					$settings['type'] = true;
				}

			break;			
		}		

		//Element ID		
		if (! array_key_exists('element_id', $settings) || $settings['element_id'] == '') {
			$settings['element_id'] = Powerfolio_Common_Settings::generate_element_id();
		}	

		return $settings;			
	}

	/*
	* Get Items for grid/portfolio
	*/
	public static function get_items_for_grid($settings, $widget) {

		$items = array();

		switch ($widget) {
			case 'portfolio':
				if(! $settings['post_type'] || $settings['post_type'] == '') {
					$settings['post_type'] = 'elemenfolio';
				}	
		
				if ( $settings['type'] == true) {
					$args = array(
						'post_type' => $settings['post_type'],
						'posts_per_page' => $settings['postsperpage'],
						'suppress_filters' => false,
						'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'elemenfoliocategory',
								'field'    => 'term_id',
								'terms'    => $settings['taxonomy'],
							),
						),		
						//'p' => $id
					); 	
				} else { 
					$args = array(
						'post_type' => $settings['post_type'],
						'posts_per_page' => $settings['postsperpage'],	
						'suppress_filters' => false,  
					);			
				}

				$items = (array)get_posts($args);
			break;

			case 'image_gallery':

				$items = $settings['list'];

			break;

			case 'portfolio_elementor':
				// Same logic as 'portfolio' case - for Elementor widget direct calls
				if(! $settings['post_type'] || $settings['post_type'] == '') {
					$settings['post_type'] = 'elemenfolio';
				}

				if ( $settings['type'] == true) {
					$args = array(
						'post_type' => $settings['post_type'],
						'posts_per_page' => $settings['postsperpage'],
						'suppress_filters' => false,
						'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'elemenfoliocategory',
								'field'    => 'term_id',
								'terms'    => $settings['taxonomy'],
							),
						),
					);
				} else {
					$args = array(
						'post_type' => $settings['post_type'],
						'posts_per_page' => $settings['postsperpage'],
						'suppress_filters' => false,
					);
				}

				$items = (array)get_posts($args);
			break;
		}

		return (array)$items;
	}

	/*
	* Get Terms filter output
	*/
	public static function get_grid_filter($settings, $widget) {		
		if ($settings['showfilter'] === 'no' || $settings['showfilter'] === false) {
			return ''; 
		}

		$output = '';

		$output .='<div class="elpt-portfolio-filter">';						
		
			//All text filters and variables
			$settings['tax_text'] = apply_filters( 'elpt_tax_text', $settings['tax_text'] );
			$tax_text_filter = apply_filters( 'elpt_tax_text_filter', '*' );
			
			if ($settings['tax_text'] =='') {
				$settings['tax_text'] = __('All', 'portfolio-elementor');
			}
			
			if ($settings['showallbtn'] !== 'no') {
				if ($settings['type'] == true && is_array($settings['taxonomy']) && count($settings['taxonomy']) > 1 ) {
					$output .='<button class="portfolio-filter-item item-active" data-filter="'.$tax_text_filter.'" style="background-color:' .';">'.$settings['tax_text'].'</button>';
				}
				else if ($settings['type'] !== true) {
					$output .='<button class="portfolio-filter-item item-active" data-filter="'.$tax_text_filter.'" style="background-color:' .';">'.$settings['tax_text'].'</button>';
				} 
			}
			
			switch ($widget) {
				case 'portfolio':

					if ( $settings['post_type'] === 'elemenfolio' || $settings['post_type'] === '' ) {							
						$terms = get_terms( array(
							'taxonomy' => 'elemenfoliocategory',
							'hide_empty' => false,
						) );
			
						$terms = apply_filters( 'elpt_tax_terms_list', $terms );
						
			
						foreach ( $terms as $term ) {
							$thisterm = $term->name;
							$thistermslug = $term->slug;
			
							if ($settings['type'] == true && is_array($settings['taxonomy']) && in_array($term->term_id, $settings['taxonomy']) && count($settings['taxonomy']) > 1 ) {
								$output .='<button class="portfolio-filter-item" style="background-color:' .';" data-filter=".elemenfoliocategory-'.esc_attr($thistermslug).'">'.esc_html($thisterm).'</button>';
							}
							else if ($settings['type'] != true) {
								$output .='<button class="portfolio-filter-item" style="background-color:' .';" data-filter=".elemenfoliocategory-'.esc_attr($thistermslug).'">'.esc_html($thisterm).'</button>';
							}
						}				
					} else {
						return ;
					}

				break;

				// Image Gallery Widget
				case 'image_gallery':

					//Get all Tags
					$tag_list = array();
					foreach($settings['list'] as $item) {
						$tag_array = $str_arr = explode (",", $item['list_filter_tag']);
						foreach ($tag_array as $tag) {
							if ( ! in_array ($tag, $tag_list ) ){
								$tag_list[] = $tag;
							}
						}
					}

					//Sort tags in alphabetical order
					sort($tag_list);

					//Filter tag list
					$tag_list = apply_filters( 'elpt_gallery_terms_list', $tag_list );

					//List Tags
					foreach($tag_list as $item) {
						$item_slug = elpt_get_text_slug($item);
						$output .='<button class="portfolio-filter-item" style="background-color:' .';" data-filter=".elemenfoliocategory-'.esc_attr($item_slug).'">'.$item.'</button>';
					}

				break;

				// Portfolio Elementor Widget (direct call, same as portfolio)
				case 'portfolio_elementor':

					if ( $settings['post_type'] === 'elemenfolio' || $settings['post_type'] === '' ) {
						$terms = get_terms( array(
							'taxonomy' => 'elemenfoliocategory',
							'hide_empty' => false,
						) );

						$terms = apply_filters( 'elpt_tax_terms_list', $terms );

						foreach ( $terms as $term ) {
							$thisterm = $term->name;
							$thistermslug = $term->slug;

							if ($settings['type'] == true && is_array($settings['taxonomy']) && in_array($term->term_id, $settings['taxonomy']) && count($settings['taxonomy']) > 1 ) {
								$output .='<button class="portfolio-filter-item" style="background-color:' .';" data-filter=".elemenfoliocategory-'.esc_attr($thistermslug).'">'.esc_html($thisterm).'</button>';
							}
							else if ($settings['type'] != true) {
								$output .='<button class="portfolio-filter-item" style="background-color:' .';" data-filter=".elemenfoliocategory-'.esc_attr($thistermslug).'">'.esc_html($thisterm).'</button>';
							}
						}
					} else {
						return ;
					}

				break;

			}

			$output .='</div>';	
		
		return (string)$output;
	}
	

	/*
	* get_columns_css_classes()
	*/
	static function get_columns_css_classes($settings) {

		$portfoliocolumns = 'elpt-portfolio-columns-4';
		
		if ($settings['columns'] == '2') {
			$portfoliocolumns = 'elpt-portfolio-columns-2';
		}
		else if ($settings['columns'] == '3') {
			$portfoliocolumns = 'elpt-portfolio-columns-3';
		}
		else if ($settings['columns'] == '5') {
			$portfoliocolumns = 'elpt-portfolio-columns-5';
		}
		else if ($settings['columns'] == '6') {
			$portfoliocolumns = 'elpt-portfolio-columns-6';
		}

		return $portfoliocolumns;
	}

	/*
	* get_columns_class_for_mobile()
	*/
	static function get_columns_class_for_mobile($settings) {

		$portfoliocolumns_mobile = '';

		if ( array_key_exists('columns_mobile', $settings) ) {

			// Skip class generation for 'custom' - let responsive controls handle it
			if ( $settings['columns_mobile'] == 'custom') {
				$portfoliocolumns_mobile = '';
			}
			else if ( $settings['columns_mobile'] == '1') {
				$portfoliocolumns_mobile = 'elpt-portfolio-columns-mobile-1';
			}
			else if ( $settings['columns_mobile'] == '2') {
				$portfoliocolumns_mobile = 'elpt-portfolio-columns-mobile-2';
			}
			else if ( $settings['columns_mobile'] == '3') {
				$portfoliocolumns_mobile = 'elpt-portfolio-columns-mobile-3';
			}
		}

		return $portfoliocolumns_mobile;
	}

	/*
	* get_margin_css_class()
	*/
	static function get_margin_css_class($settings) {
		$portfolio_margin_css_class = '';

		if ( $settings['margin'] === 'yes' || $settings['margin'] === true || $settings['margin'] === 'true' ) {
			$portfolio_margin_css_class = 'elpt-portfolio-margin';
		}

		return $portfolio_margin_css_class;
	}


	/*
	* get_portfolio_styles()
	*/
	static function get_portfolio_styles($settings) {
		$styles = array();

		$styles['portfoliostyle'] = '';
		$styles['portfolio_isotope'] = 'elpt-portfolio-content-isotope';

		if ( isset($settings['pagination'] ) && $settings['pagination'] == 'true' ) {
			$styles['portfolio_isotope'] = 'elpt-portfolio-content-isotope-pro';
		}
		
		if ($settings['style'] == 'masonry' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-style-masonry';
		}
		else if ($settings['style'] == 'specialgrid1' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-1';
		}
		else if ($settings['style'] == 'specialgrid2' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-2';
		}
		else if ($settings['style'] == 'specialgrid3' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-3';
		}
		else if ($settings['style'] == 'specialgrid4' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-4';
		}
		else if ($settings['style'] == 'specialgrid5' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-5';
		}	
		else if ($settings['style'] == 'specialgrid6' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-6';
		}
		else if ($settings['style'] == 'specialgrid7' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-special-grid-7';
			// Uses fitRows instead of packery to prevent item climbing
			// Note: This grid has fixed height, so Additional Margin uses margin-bottom (see portfolio_widget.php)
			// Check if pagination is active - preserve the -pro class for pagination JS
			if ( isset($settings['pagination'] ) && $settings['pagination'] == 'true' ) {
				$styles['portfolio_isotope'] = 'elpt-portfolio-content-fitrows elpt-portfolio-content-isotope-pro';
			} else {
				$styles['portfolio_isotope'] = 'elpt-portfolio-content-fitrows';
			}
		}
		else if ($settings['style'] == 'purchasedgrid' ) {
			$styles['portfoliostyle'] = apply_filters( 'powerfolio_custom_style_class_filter', 'elpt-portfolio-purchased-grid');
			$styles['portfolio_isotope'] = apply_filters( 'powerfolio_custom_isotope_class_filter', 'elpt-portfolio-content-isotope');
			$styles['portfoliocolumns'] = apply_filters( 'powerfolio_custom_cols_class_filter', 'elpt-portfolio-columns-3');
		}
		else if ($settings['style'] == 'grid_builder' ) {
			$styles['portfoliostyle'] = 'elpt-portfolio-grid-builder';

			// Add Fixed Layout class if enabled
			if (isset($settings['grid_fixed_layout']) && $settings['grid_fixed_layout'] == 'yes') {
				$styles['portfoliostyle'] .= ' elpt-fixed-layout-mode';
			}

			// Check if pagination is active - preserve the -pro class for pagination JS
			if ( isset($settings['pagination'] ) && $settings['pagination'] == 'true' ) {
				$styles['portfolio_isotope'] = 'elpt-portfolio-content-packery elpt-portfolio-content-isotope-pro';
			} else {
				$styles['portfolio_isotope'] = 'elpt-portfolio-content-packery';
			}
		}					
		else {
			$styles['portfoliostyle'] = 'elpt-portfolio-style-box';
		}
	
		return $styles;
	}

	/*
	* get_portfolio_link_data()
	*/
	public static function get_portfolio_link_data($post, $settings, $widget, $portfolio_image) {
        		
        $portfolio_link_target = '';
        $portfolio_link_rel = '';
        $portfolio_link_class = '';
        $portfolio_link_follow = '';
		$portfolio_link = '';

		$rel_id = Powerfolio_Common_Settings::generate_element_id(); 
		
		if ( array_key_exists('element_id', $settings) ) {
			$rel_id = $settings['element_id'];
		}		
		
		if ( $widget == 'portfolio' || $widget == 'portfolio_elementor' ) {
			$portfolio_link = get_the_permalink($post['ID']);
		}

        // Determina qual é a configuração de link a ser usada:
        // 1. Prioriza a configuração individual da imagem ($post['linkto']) se disponível
        // 2. Usa a configuração global ($settings['linkto']) como fallback
        $link_type = '';
        
        if (array_key_exists('linkto', $post) && !empty($post['linkto'])) {
            // Usa a configuração específica da imagem
            $link_type = $post['linkto'];
        } else if (array_key_exists('linkto', $settings)) {
            // Usa a configuração global
            $link_type = $settings['linkto'];
        }
        
        // Processa o link de acordo com o tipo
        if ($link_type == 'image') {
            $portfolio_link = $portfolio_image;
            $portfolio_link_class = 'elpt-portfolio-lightbox';
            $portfolio_link_rel = 'rel=elpt-portfolio_' . $rel_id;
        } 
		else if ($link_type == 'image_elementor') {
            $portfolio_link = $portfolio_image;
            $portfolio_link_class = 'elpt-portfolio-elementor-lightbox';
            $portfolio_link_rel = 'rel="elpt-portfolio_' . $rel_id . '"';
        } 
		else if ($link_type == 'video' && array_key_exists('video_url', $post) && !empty($post['video_url'])) {
            // For YouTube and Vimeo videos
            // Format the video URL for SimpleLightbox to process correctly
            $video_url = $post['video_url'];
            
            // Instead of using the URL directly as link, we'll link to the image
            // and use data-attributes for the video
            $portfolio_link = $portfolio_image; // Link to image (for compatibility)
            $portfolio_link_class = 'elpt-portfolio-video-lightbox';
            $portfolio_link_rel = 'rel=elpt-portfolio_' . $rel_id;
            
            // Add data attributes for video lightbox
            $portfolio_link_data_video = 'data-video="' . esc_url($video_url) . '"';
        }
		else if ($link_type == 'link' && array_key_exists('list_external_link', $post)) {
            $portfolio_link = $post['list_external_link']['url'];
            if ($post['list_external_link']['is_external'] == true) {
                $portfolio_link_target = 'target="_blank"';
            }
            if ($post['list_external_link']['nofollow'] == true) {
                $portfolio_link_follow = 'rel="nofollow"';
            }
        }

        // Initialize the return array with default values
        $return_array = [
            'link' => $portfolio_link,
            'target' => $portfolio_link_target,
            'rel' => $portfolio_link_rel,
            'class' => $portfolio_link_class,
            'follow' => $portfolio_link_follow,
        ];
        
        // Add data attributes for video if defined
        if (isset($portfolio_link_data_video)) {
            $return_array['portfolio_link_data_video'] = $portfolio_link_data_video;
        }
        
        return $return_array;
    }

	/*
	* get_portfolio_terms()
	*/
	public static function get_portfolio_terms($post, $widget) {
        $term_names = [];

        if ($widget == 'portfolio' || $widget == 'portfolio_elementor') {
            $terms = get_the_terms($post['ID'], 'elemenfoliocategory');
            if (is_array($terms) || is_object($terms)) {
                foreach ($terms as $term) {
                    $term_names[] = $term->name;
                }
            }
        } else if ($widget == 'image_gallery') {
            $tag_array = explode(",", $post['list_filter_tag']);
            foreach ($tag_array as $tag) {
                $term_names[] = $tag;
            }
        }

        return $term_names;
    }

	/*
	* get_single_item_data()
	*/
	static function get_single_item_data($post, $settings, $widget) {
		$data = array();			
	
		switch ($widget) {
			case 'portfolio':
				$data['post_id'] = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
				$data['post_title'] = get_the_title( $data['post_id'] );
				$data['portfolio_image'] = Powerfolio_Common_Settings::get_post_portfolio_image_url( $data['post_id'] );
		
				$data['classes'] = get_post_class($data['post_id']);

				if ( $settings['post_type'] == 'elemenfolio' ) {
					$terms = get_the_terms($data['post_id'], 'elemenfoliocategory');
					if ( is_array( $terms ) ) {
						foreach ($terms as $term) {
							if (!in_array('elemenfoliocategory-' . $term->slug, $data['classes'])) {
								$data['classes'][] = 'elemenfoliocategory-' . $term->slug;
							}
						}	
					}									
				}
				
				$data['classes'] = join(' ', $data['classes']);

			break;

			case 'image_gallery':
				if ( array_key_exists('list_description', $post) ) {
					$data['list_description'] = $post['list_description'];
				}

				$data['post_title'] = $post['list_title'];

				$data['portfolio_image'] = '';
				if ( ! empty( $post['list_image']['url'] ) ) {
					$data['portfolio_image'] = $post['list_image']['url'];
				} elseif ( ! empty( $post['list_image']['id'] ) ) {
					$data['portfolio_image'] = Powerfolio_Common_Settings::get_image_url( $post['list_image']['id'], 'large' );
				}

				// Process hover image (backward compatible)
				$data['hover_image'] = '';
				if ( array_key_exists('list_hover_image', $post) && !empty($post['list_hover_image']['url']) ) {
					$data['hover_image'] = $post['list_hover_image']['url'];
				}

				// Process item icon (per item for image gallery)
				if ( array_key_exists('list_icon', $post) && !empty($post['list_icon']['value']) ) {
					$data['item_icon'] = $post['list_icon'];
				}

				$tag_array = explode(",", $post['list_filter_tag']);

				$data['classes'] = '';

				foreach ($tag_array as $tag) {
					$data['classes'] .= ' elemenfoliocategory-' . elpt_get_text_slug($tag);
				}

			break;

			case 'portfolio_elementor':
				// Same as 'portfolio' case - for Elementor widget direct calls
				$data['post_id'] = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
				$data['post_title'] = get_the_title( $data['post_id'] );
				$data['portfolio_image'] = Powerfolio_Common_Settings::get_post_portfolio_image_url( $data['post_id'] );

				$data['classes'] = get_post_class($data['post_id']);

				if ( $settings['post_type'] == 'elemenfolio' ) {
					$terms = get_the_terms($data['post_id'], 'elemenfoliocategory');
					if ( is_array( $terms ) ) {
						foreach ($terms as $term) {
							if (!in_array('elemenfoliocategory-' . $term->slug, $data['classes'])) {
								$data['classes'][] = 'elemenfoliocategory-' . $term->slug;
							}
						}
					}
				}

				$data['classes'] = join(' ', $data['classes']);

			break;
		}

		// Process global item icon from settings (for portfolio widget)
		// This is set globally and applies to all items
		if ( !isset($data['item_icon']) && isset($settings['item_icon']) && !empty($settings['item_icon']['value']) ) {
			$data['item_icon'] = $settings['item_icon'];
		}

		// Terms
		$data['term_names'] = self::get_portfolio_terms($post, $widget);
		// Link Data
		$data['link_data'] = self::get_portfolio_link_data($post, $settings, $widget, $data['portfolio_image']);
	
		return $data;
	}
	

	/*
	* Get settings for shortcode
	*/
	public static function get_shortcode_settings($settings, $widget) {
		// Get widget settings
		$settings = self::get_widget_settings($settings, $widget);
		if ( is_array( $settings['taxonomy'] ) ) {
			$settings['taxonomy'] = implode( ',', array_filter( $settings['taxonomy'] ) );
		}
		$settings['taxonomy'] = explode( ',', (string) $settings['taxonomy'] );
	
		// Columns
		$settings['portfoliocolumns'] = self::get_columns_css_classes($settings);
	
		// Columns Mobile
		$settings['portfoliocolumns_mobile'] = self::get_columns_class_for_mobile($settings);
	
		// Margin
		$settings['portfoliomargin'] = self::get_margin_css_class($settings);
	
		// Styles
		$styles = self::get_portfolio_styles($settings);
		$settings['portfoliostyle'] = $styles['portfoliostyle'];
	
		if (!empty($styles['portfolio_isotope'])) {
			$settings['portfolio_isotope'] = $styles['portfolio_isotope'];
		}
		if (!empty($styles['portfoliocolumns'])) {
			$settings['portfoliocolumns'] = $styles['portfoliocolumns'];
		}

		return Powerfolio_Styles::merge_layout_settings( $settings );
	}


	/*
	* Get single item Output
	*/
	static function get_single_item_output($post, $settings, $widget) {

		// Get data for single item
		$data = self::get_single_item_data($post, $settings, $widget);

		// Check if this hover style uses the "content-below" layout
		$hover_style = isset($settings['hover']) ? $settings['hover'] : '';
		$is_content_below = Powerfolio_Common_Settings::is_content_below_style($hover_style);

		$output = '';

		// Add layout class to wrapper for content-below styles
		$wrapper_class = $is_content_below ? ' elpt-layout-content-below' : '';
		$output .= '<div class="portfolio-item-wrapper ' . $data['classes'] . $wrapper_class . '">';

			// Check if additional data attributes for video exist
			$video_data_attr = '';
			if (isset($data['link_data']['portfolio_link_data_video'])) {
				$video_data_attr = ' ' . $data['link_data']['portfolio_link_data_video'] . ' ';
			}

			// Sanitize title for lightbox to prevent XSS (SimpleLightbox vulnerability fix)
			$safe_title = wp_strip_all_tags($data['post_title']);
			$safe_title = esc_attr($safe_title);

			// Add hover class if hover image exists
			$hover_class = !empty($data['hover_image']) ? ' elpt-has-hover-image' : '';

			$output .= '<a href="' . esc_url($data['link_data']['link']) . '" class="portfolio-item ' . esc_attr($data['link_data']['class']) . $hover_class . '" ' . esc_attr($data['link_data']['rel']) . ' style="background-image: url(' . esc_url($data['portfolio_image']) . ')" title="' . $safe_title . '" ' . $data['link_data']['target'] . ' ' . $data['link_data']['follow'] . $video_data_attr . '">';

				$output .= '<img src="' . esc_url($data['portfolio_image']) . '" class="elpt-main-image" title="' . $safe_title . '" alt="' . $safe_title . '"/>';

				// Add hover image if exists (backward compatible)
				if (!empty($data['hover_image'])) {
					$output .= '<img src="' . esc_url($data['hover_image']) . '" class="elpt-hover-image" title="' . $safe_title . '" alt="' . $safe_title . '" style="display: none;"/>';
				}

				// For standard layouts: info wrapper is INSIDE the link (overlay effect)
				if ( ! $is_content_below ) {
					$output .= self::get_item_info_html($data, $settings);
				}

			$output .= '</a>';

			// For content-below layouts: info wrapper is OUTSIDE the link (below image)
			if ( $is_content_below ) {
				$output .= self::get_item_info_html($data, $settings);
			}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the HTML for item info (title, description, categories).
	 * Extracted to avoid code duplication between standard and content-below layouts.
	 *
	 * @param array $data Item data
	 * @param array $settings Widget settings
	 * @return string HTML output
	 */
	private static function get_item_info_html($data, $settings) {
		$output = '';

		// Check if this is a content-below style (for different icon positioning)
		$hover_style = isset($settings['hover']) ? $settings['hover'] : '';
		$is_content_below = Powerfolio_Common_Settings::is_content_below_style($hover_style);

		// Render icon HTML if exists
		$icon_html = '';
		if ( isset($data['item_icon']) && !empty($data['item_icon']['value']) ) {
			$icon_html = self::render_item_icon($data['item_icon']);
		}

		// Add modifier class when icon is present for CSS targeting
		$infos_class = !empty($icon_html) ? 'portfolio-item-infos has-icon' : 'portfolio-item-infos';
		$output .= '<div class="portfolio-item-infos-wrapper" style="background-color:' . ';"><div class="' . $infos_class . '">';

			// For content-below layouts: icon and text content are siblings for flexbox layout
			if ( $is_content_below && !empty($icon_html) ) {
				// Icon wrapper
				$output .= '<div class="portfolio-item-icon-wrapper">' . $icon_html . '</div>';

				// Text content wrapper
				$output .= '<div class="portfolio-item-text-content">';
			} else {
				// For standard layouts: icon above title
				if ( !empty($icon_html) ) {
					$output .= '<div class="portfolio-item-icon-wrapper">' . $icon_html . '</div>';
				}
			}

			// Title
			if ($settings['hide_item_title'] != 'yes') {
				$output .= '<div class="portfolio-item-title"><span class="portfolio-item-title-span">' . $data['post_title'] . '</span></div>';
			}

			// Description
			if (array_key_exists('list_description', $data) && $data['list_description'] != '') {
				$output .= '<div class="portfolio-item-desc">' . $data['list_description'] . '</div>';
			}

			// Categories / Tags
			if ($settings['hide_item_category'] != 'yes') {
				$output .= '<div class="portfolio-item-category">';

				foreach ($data['term_names'] as $term_name) {
					$output .= '<span class="elpt-portfolio-cat">' . esc_html($term_name) . '</span>';
				}

				$output .= '</div>';
			}

			// Close text content wrapper for content-below layouts
			if ( $is_content_below && !empty($icon_html) ) {
				$output .= '</div>'; // Close .portfolio-item-text-content
			}

		$output .= '</div></div>';

		return $output;
	}

	/**
	 * Render an Elementor icon control value.
	 *
	 * @param array $icon The icon control value array with 'value' and 'library' keys
	 * @return string HTML output of the icon
	 */
	private static function render_item_icon($icon) {
		if ( empty($icon) || empty($icon['value']) ) {
			return '';
		}

		// Check if Elementor Icons_Manager is available
		if ( ! class_exists('\Elementor\Icons_Manager') ) {
			return '';
		}

		// Use output buffering to capture the icon HTML
		ob_start();
		\Elementor\Icons_Manager::render_icon(
			$icon,
			[ 'aria-hidden' => 'true', 'class' => 'powerfolio-item-icon' ]
		);
		return ob_get_clean();
	}	


	/**
	 * Allow portfolio markup (inline background images, etc.) through sanitization.
	 *
	 * @param string $html Portfolio HTML.
	 * @return string
	 */
	private static function kses_portfolio_output( $html ) {
		$allowed = wp_kses_allowed_html( 'post' );

		foreach ( array( 'a', 'div', 'span', 'img', 'input', 'button' ) as $tag ) {
			if ( ! isset( $allowed[ $tag ] ) ) {
				$allowed[ $tag ] = array();
			}
			$allowed[ $tag ]['class'] = true;
			$allowed[ $tag ]['id']    = true;
			$allowed[ $tag ]['style'] = true;
		}

		$allowed['a']['href']   = true;
		$allowed['a']['target'] = true;
		$allowed['a']['rel']    = true;
		$allowed['a']['title']  = true;

		$allowed['img']['src']   = true;
		$allowed['img']['alt']   = true;
		$allowed['img']['title'] = true;

		$allowed['input']['type']  = true;
		$allowed['input']['value'] = true;

		return wp_kses( $html, $allowed );
	}

	/*
	* Create shortcode and returns the output
	*/
	public static function get_portfolio_shortcode_output($settings, $content = null, $shortcode = null, $widget="portfolio") {
		
		// enqueue scripts for shortcode
		if (! is_null($shortcode) ) {
			self::enqueue_scripts();
		}		
	
		// Get settings
		$settings = self::get_shortcode_settings($settings, $widget);

		if ( isset( $settings['pagination'] ) && 'true' === $settings['pagination'] ) {
			$per_page = ! empty( $settings['pagination_postsperpage'] ) ? (int) $settings['pagination_postsperpage'] : 6;
			wp_localize_script(
				'elpt-portfoliojs',
				'powerfolioGridSettings',
				array(
					'itemsPerPageDefault' => max( 1, $per_page ),
					'paginationMode'      => ! empty( $settings['pagination_mode'] ) ? $settings['pagination_mode'] : 'load_more',
					'loadMoreLabel'       => __( 'Load more projects', 'portfolio-elementor' ),
					'loadingLabel'        => __( 'Loading…', 'portfolio-elementor' ),
				)
			);
			// Back-compat for older inline scripts.
			wp_add_inline_script(
				'elpt-portfoliojs',
				'var gridSettings = typeof powerfolioGridSettings !== "undefined" ? powerfolioGridSettings : {};',
				'before'
			);
		}
	
		// Get widget items
		$portfolio_items = self::get_items_for_grid($settings, $widget);

		// Workarounds
		// To-do: Fix missing array keys error
		if (! array_key_exists('zoom_effect', $settings) ) {
			$settings['zoom_effect'] = '';
		}
		if (! array_key_exists('portfolio_isotope', $settings) ) {
			$settings['portfolio_isotope'] = '';
		}
	
		if (count($portfolio_items)) {
			$output = '';

			$layout_classes = Powerfolio_Styles::get_wrapper_classes( $settings );
			$inline_vars    = Powerfolio_Styles::get_inline_css_variables( $settings );
			$wrapper_class  = trim( 'elpt-portfolio ' . $settings['element_id'] . ' ' . $layout_classes );

			$output .= '<div class="' . esc_attr( $wrapper_class ) . '" style="' . esc_attr( $inline_vars ) . '">';

				//Filter
				$output .= self::get_grid_filter($settings, $widget);

				$output .= '<div class="elpt-portfolio-content ' . $settings['portfolio_isotope'] . ' ' . $settings['portfoliostyle'] . ' ' . $settings['zoom_effect'] . ' ' . $settings['hover'] . ' ' . $settings['portfoliocolumns'] . ' ' . $settings['portfoliocolumns_mobile'] . ' ' . $settings['portfoliomargin'] . '">';

				foreach ($portfolio_items as $post) {
					$output .= self::get_single_item_output((array)$post, $settings, $widget);
				}

				$output .= '</div>';

				if ( isset( $settings['pagination'] ) && 'true' === $settings['pagination'] ) {
					$output .= '<div class="elpt-portfolio-pagination" aria-hidden="true"></div>';
				}

			$output .= '</div>';

			return self::kses_portfolio_output( $output );
		} else {
			// No portfolio items found - display helpful message
			$empty_message = apply_filters(
				'powerfolio_empty_message',
				__('No items found for the selected post type in this portfolio widget.', 'portfolio-elementor')
			);

			$output = '<div class="elpt-portfolio-empty-message" style="padding: 20px; text-align: center; color: #666;">';
			$output .= '<p>' . esc_html($empty_message) . '</p>';
			$output .= '</div>';

			return self::kses_portfolio_output( $output );
		}
	}

	//Register the shortcode shortcode
	public function register_portfolio_shortcodes() {	
	  add_shortcode("powerfolio", array( __CLASS__, 'get_portfolio_shortcode_output') );
      add_shortcode("elemenfolio", array( __CLASS__, 'get_portfolio_shortcode_output') );
	}	
}
