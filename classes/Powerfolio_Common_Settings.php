<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Portfolio: Customization Options
 *
 */
class Powerfolio_Common_Settings {
    public function __construct() {
    }

    public static function get_yes_no_options() {
        return [
            'yes' => __( 'Yes', 'portfolio-elementor' ),
            'no'  => __( 'No', 'portfolio-elementor' ),
        ];
    }

    public static function get_column_options() {
        $column_array = array(
            '2' => __( 'Two Columns', 'portfolio-elementor' ),
            '3' => __( 'Three Columns', 'portfolio-elementor' ),
            '4' => __( 'Four Columns', 'portfolio-elementor' ),
            '5' => __( 'Five Columns', 'portfolio-elementor' ),
            '6' => __( 'Six Columns', 'portfolio-elementor' ),
        );
        return $column_array;
    }

    public static function get_column_mobile_options() {
        $column_mobile_array = array(
            'custom' => __( 'Custom (Grid Builder)', 'portfolio-elementor' ),
            '1'      => __( 'One Column', 'portfolio-elementor' ),
            '2'      => __( 'Two Columns', 'portfolio-elementor' ),
            '3'      => __( 'Three Columns', 'portfolio-elementor' ),
        );
        return $column_mobile_array;
    }

    public static function get_grid_options() {
        $grid_options = array(
            'masonry'       => __( 'Masonry', 'portfolio-elementor' ),
            'box'           => __( 'Boxes', 'portfolio-elementor' ),
            'purchasedgrid' => __( 'Customized Grid Service', 'portfolio-elementor' ),
        );
        return $grid_options;
    }

    public static function get_hover_options() {
        $grid_options = array();
        $grid_options = array(
            'simple'  => __( 'Style 0: Simple', 'portfolio-elementor' ),
            'hover1'  => __( 'Style 1: From Bottom', 'portfolio-elementor' ),
            'hover2'  => __( 'Style 2: From Top', 'portfolio-elementor' ),
            'hover16' => __( 'Style 16: Content Visible 2', 'portfolio-elementor' ),
            'hover17' => __( 'Style 17: Content Visible 1', 'portfolio-elementor' ),
        );
        return $grid_options;
    }

    public static function get_lightbox_options( $source = '' ) {
        $options = array(
            'image'   => __( 'Image (with the Powerfolio lightbox)', 'portfolio-elementor' ),
            'project' => __( 'Project Details Page', 'portfolio-elementor' ),
        );
        if ( $source == 'elementor' ) {
            $options['image_elementor'] = __( 'Image (with Elementor default lightbox)', 'portfolio-elementor' );
        }
        return $options;
    }

    public static function get_post_types( $args = array() ) {
        if ( empty( $args ) ) {
            $args = array(
                'public' => true,
            );
        }
        return get_post_types( $args );
    }

    public static function get_portfolio_taxonomy_terms() {
        $terms = get_terms( array(
            'taxonomy'   => 'elemenfoliocategory',
            'fields'     => 'id=>name',
            'hide_empty' => false,
        ) );
        return $terms;
    }

    public static function generate_element_id( $key = 'elpt_powerfolio' ) {
        return $key . '_' . wp_rand( 0, 99999 );
    }

    public static function get_upgrade_message( $source = '' ) {
        return '';
    }

    /**
     * Featured image URL for portfolio CPT items.
     *
     * @param int    $post_id Post ID.
     * @param string $size    Image size.
     * @return string
     */
    public static function get_post_portfolio_image_url( $post_id, $size = 'large' ) {
        $post_id = (int) $post_id;
        if ( ! $post_id ) {
            return '';
        }

        $url = get_the_post_thumbnail_url( $post_id, $size );
        if ( $url ) {
            return $url;
        }

        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            return self::get_image_url( $thumbnail_id, $size );
        }

        return '';
    }

    /**
     * Get hover styles that use "content-below" layout structure.
     * These styles render the info wrapper OUTSIDE the link element,
     * allowing the description to appear below the image without overlay.
     *
     * @return array List of hover style keys that use content-below layout
     */
    public static function get_content_below_hover_styles() {
        return array('hover22');
    }

    /**
     * Check if a hover style uses the content-below layout structure.
     *
     * @param string $hover_style The hover style key (e.g., 'hover22', 'simple')
     * @return bool True if the style uses content-below layout
     */
    public static function is_content_below_style( $hover_style ) {
        return in_array( $hover_style, self::get_content_below_hover_styles(), true );
    }

    /*
     * get_image_url_for_gallery
     */
    public static function get_image_url( $img_identifier, $img_size = '' ) {
        $image_url = '';
        $img_identifier = is_scalar( $img_identifier ) ? $img_identifier : '';

        if ( is_numeric( $img_identifier ) && (int) $img_identifier > 0 ) {
            $attachment_id = (int) $img_identifier;
            if ( $img_size ) {
                $src = wp_get_attachment_image_src( $attachment_id, $img_size );
                if ( ! empty( $src[0] ) ) {
                    $image_url = $src[0];
                }
            }
            if ( ! $image_url ) {
                $image_url = wp_get_attachment_url( $attachment_id );
            }
        } elseif ( is_string( $img_identifier ) && filter_var( $img_identifier, FILTER_VALIDATE_URL ) ) {
            $image_url = $img_identifier;
        }

        return apply_filters(
            'powerfolio_filter_image_url',
            $image_url ? $image_url : '',
            $img_identifier,
            $img_size
        );
    }

}
