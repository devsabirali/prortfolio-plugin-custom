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
     * Resolve attachment ID for a portfolio item (featured image + fallbacks).
     *
     * @param int $post_id Post ID.
     * @return int Attachment ID or 0.
     */
    public static function resolve_portfolio_thumbnail_id( $post_id ) {
        $post_id = (int) $post_id;
        if ( ! $post_id ) {
            return 0;
        }

        $stored_id = (int) get_post_meta( $post_id, '_thumbnail_id', true );
        if ( $stored_id > 0 && wp_attachment_is_image( $stored_id ) ) {
            return $stored_id;
        }

        $elementor_id = self::get_thumbnail_id_from_elementor( $post_id );
        if ( $elementor_id > 0 ) {
            return $elementor_id;
        }

        $attachments = get_posts(
            array(
                'post_parent'    => $post_id,
                'post_type'      => 'attachment',
                'post_mime_type' => 'image',
                'posts_per_page' => 1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'fields'         => 'ids',
            )
        );
        if ( ! empty( $attachments[0] ) ) {
            return (int) $attachments[0];
        }

        $content_id = self::get_thumbnail_id_from_post_content( $post_id );
        if ( $content_id > 0 ) {
            return $content_id;
        }

        return 0;
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

        $thumbnail_id = self::resolve_portfolio_thumbnail_id( $post_id );
        if ( $thumbnail_id > 0 ) {
            $url = self::get_image_url( $thumbnail_id, $size );
            if ( $url ) {
                return $url;
            }
        }

        return '';
    }

    /**
     * Find first image attachment ID inside Elementor JSON data.
     *
     * @param int $post_id Post ID.
     * @return int
     */
    public static function get_thumbnail_id_from_elementor( $post_id ) {
        $raw = get_post_meta( $post_id, '_elementor_data', true );
        if ( empty( $raw ) ) {
            return 0;
        }

        if ( is_string( $raw ) ) {
            $raw = json_decode( $raw, true );
        }

        if ( ! is_array( $raw ) ) {
            return 0;
        }

        return self::find_image_id_in_elementor_elements( $raw );
    }

    /**
     * Walk Elementor elements tree for image widget / background images.
     *
     * @param array $elements Elementor elements.
     * @return int
     */
    private static function find_image_id_in_elementor_elements( $elements ) {
        foreach ( $elements as $element ) {
            if ( ! is_array( $element ) ) {
                continue;
            }

            if ( ! empty( $element['settings'] ) && is_array( $element['settings'] ) ) {
                $settings = $element['settings'];

                foreach ( array( 'image', 'background_image', 'bg_image' ) as $key ) {
                    if ( empty( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
                        continue;
                    }

                    $image = $settings[ $key ];
                    if ( empty( $image['id'] ) || self::is_placeholder_image_url( isset( $image['url'] ) ? $image['url'] : '' ) ) {
                        continue;
                    }

                    $attachment_id = (int) $image['id'];
                    if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
                        return $attachment_id;
                    }
                }
            }

            if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $nested = self::find_image_id_in_elementor_elements( $element['elements'] );
                if ( $nested > 0 ) {
                    return $nested;
                }
            }
        }

        return 0;
    }

    /**
     * Parse classic editor / block content for an image attachment.
     *
     * @param int $post_id Post ID.
     * @return int
     */
    public static function get_thumbnail_id_from_post_content( $post_id ) {
        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) || $content === '' ) {
            return 0;
        }

        if ( preg_match( '/wp-image-(\d+)/i', $content, $matches ) ) {
            $attachment_id = (int) $matches[1];
            if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
                return $attachment_id;
            }
        }

        if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
            $attachment_id = attachment_url_to_postid( $matches[1] );
            if ( $attachment_id > 0 ) {
                return (int) $attachment_id;
            }
        }

        return 0;
    }

    /**
     * Detect Elementor / theme placeholder graphics.
     *
     * @param string $url Image URL.
     * @return bool
     */
    public static function is_placeholder_image_url( $url ) {
        if ( ! is_string( $url ) || $url === '' ) {
            return true;
        }

        if ( class_exists( '\Elementor\Utils' ) ) {
            $placeholder = \Elementor\Utils::get_placeholder_image_src();
            if ( $url === $placeholder ) {
                return true;
            }
        }

        return ( false !== stripos( $url, 'placeholder' ) );
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
