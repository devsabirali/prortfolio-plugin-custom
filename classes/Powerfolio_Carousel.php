<?php

class Powerfolio_Carousel {
	
	public function __construct() {
		if ( self::is_carousel_enabled() == true ) {
			$this->register_shortcodes();
		}		
	}

	public static function is_carousel_enabled() {
		return true;
	}
	
	public function register_shortcodes() {	
		add_shortcode("portfolio-carousel", array( __CLASS__, 'get_carousel_shortcode_content') );
	}	

	public static function get_carousel_shortcode_content($atts, $content = null) {
		// Security Fix
		foreach ($atts as $key => $value) {
			$atts[$key] = esc_attr($value);
		}
		
		extract(shortcode_atts(array(
			//"ids" => ''
			"postsperpage" => '',
			"showfilter" => '',
			"taxonomy" => '',
			"type" => '',
			"style" => '',
			"columns" => '',
			"margin" => '',
			"linkto" => '',
			"hover" => '',
			"zoom_effect" => '',
			"post_type" => '',
		), $atts));

		//Enqueue Scripts
		wp_enqueue_style( 'owl-carousel-css', plugin_dir_url( __FILE__ ) . '../vendor/owl.carousel/assets/owl.carousel.css', array(), '2.3.4' );
		wp_enqueue_style( 'owl-carousel-theme-css', plugin_dir_url( __FILE__ ) . '../vendor/owl.carousel/assets/owl.theme.default.min.css', array(), '2.3.4' );
		wp_enqueue_script( 'owl-carousel-js', plugin_dir_url( __FILE__ ) . '../vendor/owl.carousel/owl.carousel.min.js', array('jquery'), '2.3.4', true );

		//portfolio module
		wp_enqueue_script( 'elpug-carousel-portfolio-js', plugin_dir_url( __FILE__ ) . '../assets/js/custom-carousel-portfolio.js', array('jquery'), '20151215', true );
		//wp_enqueue_style( 'elpug-carousel-portfolio-css', plugin_dir_url( __FILE__ ) . 'css/elpug_.css' );
		
			if ($hover == '') {
				$hover = 'hover5';
			}

			if(! $post_type || $post_type == '') {
				$post_type = 'elemenfolio';
			}

			$portfolio_type = $type;
		
			if ( $portfolio_type == 'yes') {
				$args = array(
					'post_type' => $post_type,
					'posts_per_page' => 24,
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'elemenfoliocategory',
							'field'    => 'id',
							'terms'    => $taxonomy,
						),
					),		
					//'p' => $id
				); 	
			} else { 
				$args = array(
					'post_type' => $post_type,
					'posts_per_page' => 24,
				);			
			}
		
			$portfolioposts = get_posts($args);
			
			if(count($portfolioposts)){    
		
				global $post;
				$output ='';	

				$output .='<div class="elpt-portfolio ">';	
					$output .='<div class="elpt-portfolio-content elpt-portfolio-carousel owl-carousel owl-theme'.$zoom_effect.' '.$hover.'">';

						foreach($portfolioposts as $post){
							
							$postid = $post->ID;
				
							$portfolio_image= wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), '' );	

							$portfolio_image_ready = $portfolio_image[0];

							//Fancybox or link
							$portfolio_link = get_the_permalink();

							$portfolio_link_class = '';
							$portfolio_link_rel = '';
							if ( $linkto == 'image') {
								$portfolio_link = $portfolio_image_ready;
								$portfolio_link_class = 'elpt-portfolio-lightbox';
								$portfolio_link_rel = 'rel="elpt-portfolio"';

							}
							
							$classes = join( '  ', get_post_class($postid) ); 
							
							// Sanitize title for lightbox to prevent XSS (SimpleLightbox vulnerability fix)
							$safe_title = wp_strip_all_tags(get_the_title());
							$safe_title = esc_attr($safe_title);
							
							$output .='<div class="portfolio-item-wrapper item '.$classes.'">';
								$output .='<a href="'.esc_url($portfolio_link) .'" class="portfolio-item '.esc_attr($portfolio_link_class) .'" '.esc_attr($portfolio_link_rel) .' style="background-image: url('.esc_url($portfolio_image_ready).')" title="'.$safe_title.'">';
									$output .='<img src="'.esc_url($portfolio_image_ready) .'" title="'.$safe_title.'" alt="'.$safe_title.'"/>';
									$output .='<div class="portfolio-item-infos-wrapper" style="background-color:' .';"><div class="portfolio-item-infos">';
										$output .='<div class="portfolio-item-title">'.get_the_title().'</div>';
										$output .='<div class="portfolio-item-category">';
											$terms = get_the_terms( $post->ID , 'elemenfoliocategory' );
											if (is_array($terms) || is_object($terms)) {
											foreach ( $terms as $term ) :
													$thisterm = $term->name;
													$output .='<span class="elpt-portfolio-cat">' .esc_html($thisterm) .'</span>';
												endforeach;
											}									
										$output .='</div>';
									$output .='</div></div>';
								$output .='</a>';
							$output .='</div>';

						}
						
					$output .='</div>';
				$output .='</div>';
			}
			
			else {
				$output ='';
				// No portfolio items found - display helpful message
			$empty_message = apply_filters(
				'powerfolio_carousel_empty_message',
				__('No items found for the selected post type in this portfolio carousel widget.', 'portfolio-elementor')
			);

			$output = '<div class="elpt-portfolio-carousel-empty-message" style="padding: 20px; text-align: center; color: #666;">';
			$output .= '<p>' . esc_html($empty_message) . '</p>';
			$output .= '</div>';

				
			}

			//Reset Query
			wp_reset_postdata();
				
		return $output;
	}	
}

// Start Powerfolio_Carousel
add_action( 'init', function(){
	new Powerfolio_Carousel(); 
});