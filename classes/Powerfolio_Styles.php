<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global layout / mockups styling and pagination defaults.
 */
class Powerfolio_Styles {

	const OPTION_PRESET           = 'elpt_layout_preset';
	const OPTION_ACCENT           = 'elpt_accent_color';
	const OPTION_TAB_RADIUS       = 'elpt_tab_radius';
	const OPTION_CARD_RADIUS      = 'elpt_card_radius';
	const OPTION_PAGINATION       = 'elpt_pagination_enable';
	const OPTION_PAGINATION_MODE  = 'elpt_pagination_mode';
	const OPTION_PER_PAGE         = 'elpt_pagination_per_page';

	public static function get_global_settings() {
		return array(
			'preset'           => get_option( self::OPTION_PRESET, 'mockups' ),
			'accent_color'     => get_option( self::OPTION_ACCENT, '#5c2d91' ),
			'tab_radius'       => (int) get_option( self::OPTION_TAB_RADIUS, 50 ),
			'card_radius'      => (int) get_option( self::OPTION_CARD_RADIUS, 16 ),
			'pagination'       => get_option( self::OPTION_PAGINATION, 'yes' ),
			'pagination_mode'  => get_option( self::OPTION_PAGINATION_MODE, 'load_more' ),
			'per_page'         => (int) get_option( self::OPTION_PER_PAGE, 6 ),
		);
	}

	/**
	 * Merge widget/shortcode settings with global mockups defaults.
	 *
	 * @param array $settings Portfolio settings.
	 * @return array
	 */
	public static function merge_layout_settings( $settings ) {
		$global = self::get_global_settings();

		if ( empty( $settings['layout_preset'] ) ) {
			$settings['layout_preset'] = $global['preset'];
		}

		if ( empty( $settings['accent_color'] ) ) {
			$settings['accent_color'] = $global['accent_color'];
		}

		if ( ! isset( $settings['tab_radius'] ) || $settings['tab_radius'] === '' ) {
			$settings['tab_radius'] = $global['tab_radius'];
		}

		if ( ! isset( $settings['card_radius'] ) || $settings['card_radius'] === '' ) {
			$settings['card_radius'] = $global['card_radius'];
		}

		if ( empty( $settings['pagination_mode'] ) ) {
			$settings['pagination_mode'] = $global['pagination_mode'];
		}

		if ( empty( $settings['pagination_postsperpage'] ) ) {
			$settings['pagination_postsperpage'] = (string) $global['per_page'];
		}

		$pagination_enable = isset( $settings['pagination_enable'] ) ? $settings['pagination_enable'] : $global['pagination'];

		if ( isset( $settings['pagination_enable'] ) ) {
			if ( 'yes' === $settings['pagination_enable'] || true === $settings['pagination_enable'] || 'true' === $settings['pagination_enable'] ) {
				$settings['pagination'] = 'true';
			} elseif ( 'no' === $settings['pagination_enable'] || false === $settings['pagination_enable'] || 'false' === $settings['pagination_enable'] ) {
				$settings['pagination'] = 'false';
			}
		}

		if ( 'mockups' === $settings['layout_preset'] ) {
			if ( empty( $settings['pagination'] ) || 'false' === $settings['pagination'] ) {
				if ( 'yes' === $pagination_enable || 'true' === $pagination_enable || true === $pagination_enable ) {
					$settings['pagination'] = 'true';
				}
			}
		}

		return $settings;
	}

	/**
	 * @param array $settings Portfolio settings.
	 * @return string Extra wrapper classes.
	 */
	public static function get_wrapper_classes( $settings ) {
		$classes = array();

		if ( isset( $settings['layout_preset'] ) && 'mockups' === $settings['layout_preset'] ) {
			$classes[] = 'elpt-layout-mockups';
		}

		if ( isset( $settings['pagination'] ) && 'true' === $settings['pagination'] ) {
			$classes[] = 'elpt-has-pagination';
			$mode = isset( $settings['pagination_mode'] ) ? $settings['pagination_mode'] : 'numbers';
			$classes[] = 'elpt-pagination-' . sanitize_html_class( $mode );
		}

		return implode( ' ', $classes );
	}

	/**
	 * @param array $settings Portfolio settings.
	 * @return string Inline CSS variables for this grid instance.
	 */
	public static function get_inline_css_variables( $settings ) {
		$accent = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '#5c2d91';
		$tab_r  = isset( $settings['tab_radius'] ) ? (int) $settings['tab_radius'] : 50;
		$card_r = isset( $settings['card_radius'] ) ? (int) $settings['card_radius'] : 16;

		$accent = sanitize_hex_color( $accent );
		if ( ! $accent ) {
			$accent = '#5c2d91';
		}

		return sprintf(
			'--elpt-accent:%1$s;--elpt-accent-soft:%2$s;--elpt-tab-radius:%3$dpx;--elpt-card-radius:%4$dpx;',
			$accent,
			self::hex_to_rgba( $accent, 0.12 ),
			$tab_r,
			$card_r
		);
	}

	/**
	 * @param string $hex    Hex color.
	 * @param float  $alpha  Alpha 0-1.
	 * @return string rgba()
	 */
	private static function hex_to_rgba( $hex, $alpha ) {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );
		return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, $alpha );
	}

	public static function enqueue_assets() {
		$assets_dir = plugin_dir_url( dirname( __FILE__ ) );
		wp_enqueue_style(
			'elpt-mockups-css',
			$assets_dir . 'assets/css/powerfolio-mockups.css',
			array( 'elpt-portfolio-css' ),
			'3.2.6'
		);
	}

	public static function register_settings() {
		register_setting(
			'elpt',
			self::OPTION_PRESET,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_preset' ),
				'default'           => 'mockups',
			)
		);
		register_setting(
			'elpt',
			self::OPTION_ACCENT,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '#5c2d91',
			)
		);
		register_setting( 'elpt', self::OPTION_TAB_RADIUS, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 50,
		) );
		register_setting( 'elpt', self::OPTION_CARD_RADIUS, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 16,
		) );
		register_setting(
			'elpt',
			self::OPTION_PAGINATION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => 'yes',
			)
		);
		register_setting(
			'elpt',
			self::OPTION_PAGINATION_MODE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_pagination_mode' ),
				'default'           => 'load_more',
			)
		);
		register_setting( 'elpt', self::OPTION_PER_PAGE, array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 6,
		) );
	}

	public static function sanitize_preset( $value ) {
		return in_array( $value, array( 'default', 'mockups' ), true ) ? $value : 'mockups';
	}

	public static function sanitize_yes_no( $value ) {
		return ( 'yes' === $value ) ? 'yes' : 'no';
	}

	public static function sanitize_pagination_mode( $value ) {
		$allowed = array( 'numbers', 'load_more', 'infinite' );
		return in_array( $value, $allowed, true ) ? $value : 'load_more';
	}

	public static function get_layout_preset_options() {
		return array(
			'default' => __( 'Classic', 'portfolio-elementor' ),
			'mockups' => __( 'Mockups cards (tabs + cards)', 'portfolio-elementor' ),
		);
	}

	public static function get_pagination_mode_options() {
		return array(
			'numbers'   => __( 'Numbered pages', 'portfolio-elementor' ),
			'load_more' => __( 'Load more button', 'portfolio-elementor' ),
			'infinite'  => __( 'Infinite scroll (auto load)', 'portfolio-elementor' ),
		);
	}
}
