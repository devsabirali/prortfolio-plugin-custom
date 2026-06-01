<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ELPT_Admin_Page {

	// Construct - Hook everything
	public function __construct() {
	    add_action('admin_menu', array( __CLASS__, 'elpt_setup_menu'));
	    //add_action('admin_enqueue_scripts', array( __CLASS__, 'wpmld_css_and_js') );
	 }
	
	public static function elpt_setup_menu(){

		//Enqueue color picker
		wp_enqueue_style( 'wp-color-picker' );
		//wp_enqueue_script( 'powerfolio-js', get_template_directory_uri().'/myscript.js', array( 'wp-color-picker','jquery' ), false, true );
		wp_enqueue_script( 'powerfolio-js', plugin_dir_url( __FILE__ ) .  '../assets/js/powerfolio-admin.js', array( 'wp-color-picker' ), '20151218', true );

		//Create Admin Page
		$page_title = 'Portfolio Elementor';
		$menu_title = 'Portfolio Elementor';
		$capability = 'edit_posts';
		$menu_slug = 'elementor_portfolio';
		$function = array( __CLASS__, 'elpt_options_page');
		$icon_url = 'dashicons-layout';
		$position = 99;

		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

		//Create Settings
		$option_group = 'elpt';

		register_setting( $option_group, 'elpt_color', array(
			'type' => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default' => ''
		) );

		// Color Section
		$settings_section = 'elpt_main';
		$page = 'elpt';
		add_settings_section( $settings_section, __( 'Settings', 'portfolio-elementor' ), '', $page );
		add_settings_field( 'elpt_color', __('Color Scheme', 'portfolio-elementor'), array( __CLASS__, 'elpt_color_callback'), $page, 'elpt_main' );

		//Shortcode Section
		//add_settings_section( 'elpt_howto', __( 'How to display the portfolio grid', 'elpt' ), 'elpt_shortcode_callback', $page );
	}

	//Fields Callback
	public static function elpt_color_callback(){
		echo '<input type="text" name="elpt_color" class="color-picker" value="' .esc_attr(get_option("elpt_color")) .'"> ' . esc_html__('Select the main color of your website', 'portfolio-elementor') . ' <br>';
	}	

	//Texts
	public static function elpt_shortcode_callback() {
		
	}


	//Page
	public static function elpt_options_page() {
	?>
		<div class="wrap">
			<!-- Intro -->
			<h1><?php esc_html_e( 'Portfolio Elementor', 'portfolio-elementor' ) ?></h1>
			
			<h2><?php esc_html_e( 'Welcome to Portfolio Elementor!', 'portfolio-elementor' ) ?></h2>
			
			<div style="padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: #fff;">
				<h2><?php esc_html_e( 'Version 2.0 - New features!', 'portfolio-elementor' ) ?></h2></p>
				<ul>
					<li><strong><?php esc_html_e( '- Grid Builder:', 'portfolio-elementor' ); ?></strong> <?php esc_html_e( 'Customize the width and height of each item of your gallery grid and create exclusive layouts with a packery layout!', 'portfolio-elementor' ); ?></li>
					<li><strong><?php esc_html_e( '- Image Gallery Widget:', 'portfolio-elementor' ); ?></strong> <?php esc_html_e( 'A new widget to create filterable image galleries direct from the Elementor screen! It have the same features of the portfolio widget, but there is no need to use the portfolio post type for that.', 'portfolio-elementor' ); ?></li>
					<li><strong><?php esc_html_e( '- WP Filters:', 'portfolio-elementor' ); ?></strong> <?php esc_html_e( 'We added some filters on the plugin code, which makes it easier to customize some parts of the plugin - PHP knowledge required.', 'portfolio-elementor' ); ?></li>
					<li><strong><?php esc_html_e( '- Customization Options:', 'portfolio-elementor' ); ?></strong> <?php esc_html_e( 'We added some new customization options on widgets, and we plan to add some more!', 'portfolio-elementor' ); ?></li>
					<li><strong><?php esc_html_e( '- Code improvements:', 'portfolio-elementor' ); ?></strong> <?php esc_html_e( 'We did some code improvement to speed up the performance.', 'portfolio-elementor' ); ?></li>
				</ul>
			</div>
			<!-- /intro -->

			<!-- How to use -->
			<h2><?php esc_html_e( 'Getting Started', 'portfolio-elementor' ) ?></h2>
			<p><?php esc_html_e( "Please check the following video tutorial with the first steps on how to use the plugin.", 'portfolio-elementor' ) ?></p>

			<iframe width="560" height="315" src="https://www.youtube.com/embed/eikLVsTO0yw" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			<p><strong><?php esc_html_e( 'You can also follow the following steps to start using the plugin:', 'portfolio-elementor' ) ?></strong></p>
			<ul>
			<li>- <?php esc_html_e( 'Go to Settings > Permalinks and re-save your permalink structure.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Go to Portfolio > Add new Item', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Add the first item of your portfolio. Similar to a regular post. Make sure to add a featured image.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Repeat the steps to add some items to your portfolio.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Create a new page using Elementor.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Drag and drop the portfolio widget to your page.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Customize it using the widget options and publish the page.', 'portfolio-elementor' ) ?></li>
				<li>- <?php esc_html_e( 'Done! Your new portfolio is ready!', 'portfolio-elementor' ) ?></li>
			</ul>
			
			
			
			<!-- /How to use -->
			<br/><br/><br/><hr/><br/>
			<!-- Settings -->
			<form action="options.php" method="post">				
				<?php settings_fields( 'elpt' ); ?>
				<?php do_settings_sections( 'elpt' ); ?>
				<input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes', 'portfolio-elementor' ); ?>" class="button button-primary" />
				<br/><br/><br/><hr/><br/>
				<!-- / Settings -->				
			</form>

			<!-- Shortcode -->			
			<h2><?php esc_html_e( 'Display using a shortcode', 'portfolio-elementor' ); ?></h2>
			<p><?php esc_html_e( 'You can also display the portfolio grid on a page/post (or on Gutenberg) using the [powerfolio] shortcode.', 'portfolio-elementor' ); ?></p>
			<code>[powerfolio]</code>
			<p><?php esc_html_e( 'You can customize it using these options:', 'portfolio-elementor' ); ?></p>
				<ul>
					<li><strong><?php esc_html_e('postsperpage', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Set a number of posts to show', 'portfolio-elementor' ); ?> <i>(eg: postsperpage="12").</i></li>
					<li><strong><?php esc_html_e('type', 'portfolio-elementor' ); ?></strong>: <?php esc_html_e( 'Set it to yes if you want to show a specific portfolio category. Options: ', 'portfolio-elementor' ); ?>  <i>yes/no. (eg: type="yes")</i>.</li>
					<li><strong><?php esc_html_e('taxonomy', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Set the specific taxonomy slug. You need to set type="yes" to use this feature.', 'portfolio-elementor' ); ?>  <i>(eg: taxonomy="print")</i>.</li>
					<li><strong><?php esc_html_e('showfilter', 'portfolio-elementor' ); ?></strong>: <?php esc_html_e( 'Show the category filter on the top of the grid. Options: ', 'portfolio-elementor' ); ?>  <i> yes/no. (eg: showfilter="yes")</i>.</li>
					<li><strong><?php esc_html_e('style', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Set the grid style of the portfolio. Options: ', 'portfolio-elementor' ); ?>  <i> masonry/box. (eg: style="box")</i>.</li>
					<li><strong><?php esc_html_e('linkto', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Set the link type of the portfolio item. If is set to image, it will open the Featured Image on a lightbox. Options: ', 'portfolio-elementor' ); ?>  <i> image/project. (eg: linkto="image")</i>.</li>
					<li><strong><?php esc_html_e('columns', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Set the columns per row of the portfolio grid.  Options: ', 'portfolio-elementor' ); ?>  <i> 2/3/4. (eg: columns="4")</i>.</li>
					<li><strong><?php esc_html_e('margin', 'portfolio-elementor'); ?></strong>: <?php esc_html_e( 'Choose if you want a margin between the items or no.  Options: ', 'portfolio-elementor' ); ?>  <i> yes/no. (eg: margin="no")</i>.</li>
				</ul>
			<h3><?php esc_html_e( 'Example of a complete shortcode:', 'portfolio-elementor' ); ?></h3>
			<code>[powerfolio postsperpage="12" type="no" showfilter="yes" style="masonry" linkto="image" columns="4" margin="no"]</code>		
			<h3><?php esc_html_e( 'Example of a complete shortcode without the set properties:', 'portfolio-elementor' ); ?></h3>
			<code>[powerfolio postsperpage="" type="" taxonomy="" showfilter="" style="" linkto="" columns="" margin=""]</code>				
			<!-- /Shortcode -->
			
		</div>
		<div>
			
		</div>
	<?php
	}
}

$admin_pages = new ELPT_Admin_Page(); 