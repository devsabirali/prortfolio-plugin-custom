<?php

/*
Plugin Name: DevsAir Portfolio Elementor
Plugin URI: https://devsair.com
Description: Free portfolio and image galleries for Elementor and Gutenberg — customized by DevsAir.
Author: DevsAir
Author URI: https://devsair.com
Text Domain: portfolio-elementor
Domain Path: /languages
Version: 3.2.6
License: GPL v2 or later
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'PORTFOLIO_ELEMENTOR_VERSION' ) ) {
    define( 'PORTFOLIO_ELEMENTOR_VERSION', '3.2.6' );
}

/**
 * Set to true only when the full Freemius SDK is bundled under vendor/freemius/.
 * Disabled by default for DevsAir builds to avoid fatals from incomplete uploads.
 */
if ( ! defined( 'PORTFOLIO_ELEMENTOR_ENABLE_FREEMIUS' ) ) {
    define( 'PORTFOLIO_ELEMENTOR_ENABLE_FREEMIUS', false );
}

/**
 * Whether required Freemius SDK files exist (guards against partial vendor uploads).
 *
 * @return bool
 */
function portfolio_elementor_freemius_sdk_is_complete() {
    $sdk_root = dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk';

    $required = array(
        $sdk_root . '/start.php',
        $sdk_root . '/includes/class-freemius.php',
        $sdk_root . '/templates/connect.php',
    );

    foreach ( $required as $path ) {
        if ( ! is_readable( $path ) ) {
            return false;
        }
    }

    return true;
}

/**
 * Load the Freemius SDK when enabled and complete.
 *
 * @return bool
 */
function portfolio_elementor_load_freemius_sdk() {
    if ( ! PORTFOLIO_ELEMENTOR_ENABLE_FREEMIUS || ! portfolio_elementor_freemius_sdk_is_complete() ) {
        return false;
    }

    if ( function_exists( 'fs_dynamic_init' ) ) {
        return true;
    }

    require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';

    return function_exists( 'fs_dynamic_init' );
}

// Freemius (optional — off by default for this build).
if ( ! function_exists( 'pe_fs' ) ) {
    /**
     * @return Freemius|null
     */
    function pe_fs() {
        global $pe_fs;

        if ( isset( $pe_fs ) ) {
            return $pe_fs;
        }

        $pe_fs = null;

        if ( ! portfolio_elementor_load_freemius_sdk() ) {
            return null;
        }

        $pe_fs = fs_dynamic_init(
            array(
                'id'             => '7226',
                'slug'           => 'portfolio-elementor',
                'premium_slug'   => 'portfolio-elementor-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_75702ac7c5c10d2bfd4880c1c8039',
                'is_premium'     => true,
                'premium_suffix' => 'PRO',
                'has_addons'     => false,
                'has_paid_plans' => false,
                'menu'           => array(
                    'slug'       => 'elementor_portfolio',
                    'first-path' => 'admin.php?page=elementor_portfolio',
                ),
                'is_live'        => true,
            )
        );

        return $pe_fs;
    }
}

$portfolio_elementor_fs = pe_fs();
if ( is_object( $portfolio_elementor_fs ) && method_exists( $portfolio_elementor_fs, 'set_basename' ) ) {
    $portfolio_elementor_fs->set_basename( false, __FILE__ );
    do_action( 'pe_fs_loaded' );
}

if ( ! class_exists( 'Powerfolio_Portfolio' ) ) {
        /*
         * Start Powerfolio
         */
        /*
         * Portfolio
         */
        require 'classes/Powerfolio_Portfolio.php';
        /*
         * Image Gallery
         */
        require 'classes/Powerfolio_Image_Gallery.php';
        /*
        Portfolio Carousel
        */
        require 'classes/Powerfolio_Carousel.php';
        /*
        Powerfolio Post Grid
        */
        require 'classes/Powerfolio_Post_Grid.php';
        /*
        Powerfolio Product Grid
        */
        require 'classes/Powerfolio_Product_Grid.php';
        /*
         * Common Settings for Widgets
         */
        require 'classes/Powerfolio_Common_Settings.php';
        require 'classes/Powerfolio_Styles.php';
        /*
         * Elementor
         */
        require 'elementor/load_elementor.php';
        /*
         * Gutenberg
         */
        require 'classes/Powerfolio_Gutenberg.php';
        /*
         * Shortcode Generator
         */
        require 'classes/Powerfolio_Shortcode_Generator.php';
        /*
         * Plugin Options
         */
        require 'includes/panel.php';
        /*
         * Plugin Functions
         */
        require 'includes/functions.php';
        /*
         * Review
         */
        if ( is_admin() ) {
            require_once 'classes/Powerfolio_Feedback_Notice.php';
        }

        // Bootstrap portfolio CPT, shortcodes, and hooks once.
        new Powerfolio_Portfolio();
    }
    //Elementor Category
    if ( !function_exists( 'elpug_powerups_cat' ) ) {
        //Create Elementor Category
        function elpug_powerups_cat() {
            \Elementor\Plugin::$instance->elements_manager->add_category( 'elpug-elements', [
                'title' => __( 'DevsAir Portfolio', 'portfolio-elementor' ),
                'icon'  => 'fa fa-plug',
            ], 2 );
        }

        add_action( 'elementor/init', 'elpug_powerups_cat' );
    }
    //Post Grids Module
    if ( !class_exists( 'PWGD_Register_PwrGrids_Elementor' ) ) {
        //require 'modules/post-grid-module/post-grid-module.php';
    }
register_activation_hook( __FILE__, 'elpt_activate' );

// Enqueue general scripts
/*if (! function_exists('powerfolio_enqueue_scripts')) {   
    function powerfolio_enqueue_scripts() {
        //wp_enqueue_script( 'powerfolio-bundle-js', plugin_dir_url( __FILE__ ) . 'dist/bundle.js', array(), '1.0', true );
    }
    add_action( 'wp_enqueue_scripts', 'powerfolio_enqueue_scripts' );
}*/
//Workaround for Packery mode in some themes
if ( !function_exists( 'elpt_fix_packery_layout_themes' ) ) {
    function elpt_fix_packery_layout_themes() {
        wp_enqueue_script(
            'jquery-packery2',
            plugin_dir_url( __FILE__ ) . 'vendor/isotope/js/packery-mode.pkgd.min.js',
            array('jquery', 'imagesloaded'),
            '3.0.6',
            true
        );
    }

    add_action( 'init', function () {
        $current_theme = wp_get_theme();
        if ( $current_theme == 'Betheme' || $current_theme == 'OceanWP' ) {
            add_action( 'wp_enqueue_scripts', 'elpt_fix_packery_layout_themes', 99999 );
        }
    } );
}
//load textdomain
if ( !function_exists( 'portfolio_elementor_load_textdomain' ) ) {
    function portfolio_elementor_load_textdomain() {
        load_plugin_textdomain( 'portfolio-elementor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    add_action( 'plugins_loaded', 'portfolio_elementor_load_textdomain' );
}