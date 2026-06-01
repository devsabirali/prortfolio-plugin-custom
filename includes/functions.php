<?php

/**
 * Treat all Powerfolio features as available (no license / upgrade gate).
 */
add_filter( 'elpt-test-pro-version-filter', '__return_true' );

/**
 * Regenerate permalinks after plugin updates (fixes 404 on /portfolio/item-slug/ URLs).
 * Runs in admin only — never during REST/block-editor saves (avoids invalid JSON responses).
 */
if ( ! function_exists( 'portfolio_elementor_maybe_flush_rewrites' ) ) {
	function portfolio_elementor_maybe_flush_rewrites() {
		if ( ! defined( 'PORTFOLIO_ELEMENTOR_VERSION' ) ) {
			return;
		}

		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stored_version = get_option( 'portfolio_elementor_version', '' );

		if ( PORTFOLIO_ELEMENTOR_VERSION === $stored_version ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'portfolio_elementor_version', PORTFOLIO_ELEMENTOR_VERSION );
	}

	add_action( 'admin_init', 'portfolio_elementor_maybe_flush_rewrites' );
}

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

        if ( ! get_option( 'elpt-installDate' ) ) {
            add_option( 'elpt-installDate', gmdate( 'Y-m-d H:i:s' ) );
        }

        if ( defined( 'PORTFOLIO_ELEMENTOR_VERSION' ) ) {
            update_option( 'portfolio_elementor_version', PORTFOLIO_ELEMENTOR_VERSION );
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