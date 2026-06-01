<?php

/**
 * Treat all Powerfolio features as available (no license / upgrade gate).
 */
add_filter( 'elpt-test-pro-version-filter', '__return_true' );

//Activate PRO version
if ( !function_exists( 'elpt_activate' ) ) {
    //Flush rewrite rules after plugin activation
    function elpt_activate()
    {
        // Create an instance of the portfolio class to register CPTs
        $portfolio = new Powerfolio_Portfolio();
        
        // Register the custom post types and taxonomies immediately
        $portfolio->register_portfolio_post_type();
        $portfolio->create_portfolio_taxonomies();
        
        // Add Elementor support for the custom post type
        Powerfolio_Portfolio::add_cpt_support_for_elementor();
        
        // Flush rewrite rules to regenerate permalinks
        flush_rewrite_rules();
        
        // Set flag for future flushes if needed
        if ( !get_option( 'elpt_flush_rewrite_rules_flag' ) ) {
            add_option( 'elpt_flush_rewrite_rules_flag', true );
        }
    }  
}

//Turn text into a slug
if ( !function_exists( 'elpt_get_text_slug' ) ) {
	function elpt_get_text_slug($text) {
		// strip out all whitespace
		$text = preg_replace('/\s+/', '_', $text);
		// convert the string to all lowercase
		$text = strtolower($text);

		return $text;
	}
}