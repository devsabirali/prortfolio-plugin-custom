<?php
namespace Powerfolio\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *
 * @since 1.0.0
 */
class ELPT_Portfolio_Carousel extends Widget_Base {

	/**
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'portfolio_carousel';
	}

	/**
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Elementor Portfolio Carousel', 'portfolio-elementor' );
	}

	/**
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-elementor-square';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'elpug-elements' ];
	}

	/**
	 * Retrieve the list of scripts the widget depended on.
	 *
	 * Used to set scripts dependencies required to run the widget.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget scripts dependencies.
	 */
	public function get_script_depends() {
		return [ 'elpug' ];
	}

	/**
	 * Register the widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */

	protected function _register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Portfolio Carousel Settings', 'portfolio-elementor' ),
			]
		);

		$args = array(
			'public'   => true,
		);
		$the_post_types = get_post_types($args);
		$this->add_control(
			'post_type',
			[
				'label' => __( 'Post Type to display (default: elemenfolio)', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'elemenfolio',
				'options' => $the_post_types,
			]
		);


		$this->add_control(
			'linkto',
			[
				'label' => __( 'Each project links to', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'project',
				'options' => [
					'image' => __( 'Featured Image into Lightbox', 'portfolio-elementor' ),
					'project' => __( 'Project Details Page', 'portfolio-elementor' ),				]
			]
		);

		$this->add_control(
			'type',
			[
				'label' => __( 'Display specific portfolio category', 'portfolio-elementor' ),
				'description' => 'Only works with the "elemenfolio" post type.',
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => __( 'On', 'portfolio-elementor' ),
				'label_off' => __( 'Off', 'portfolio-elementor' ),
				'return_value' => 'yes',
			]
		);

		$portfolio_taxonomies = get_terms( array('taxonomy' => 'elemenfoliocategory', 'fields' => 'id=>name', 'hide_empty' => false, ) );
		$this->add_control(
			'taxonomy',
			[
				'label' => __( 'If yes, select wich portfolio category to show', 'portfolio-elementor' ),
				'description' => 'Only works with the "elemenfolio" post type.',
				'type' => Controls_Manager::SELECT,
				'default' => 'yes',
				'options' => $portfolio_taxonomies,
			]
		);

		$this->add_control(
			'hover',
			[
				'label' => __( 'Hover Style', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'simple',
				'options' => [
					'simple' => __( 'Simple', 'portfolio-elementor' ),
					'hover1' => __( 'From Bottom', 'portfolio-elementor' ),	
					'hover2' => __( 'From Top', 'portfolio-elementor' ),	
					'hover3' => __( 'From Right', 'portfolio-elementor' ),	
					'hover4' => __( 'From Left', 'portfolio-elementor' ),	
					'hover5' => __( 'Hover Effect 5', 'portfolio-elementor' ),	
					'hover6' => __( 'Special 1', 'portfolio-elementor' ),	
					'hover7' => __( 'Text from Left', 'portfolio-elementor' ),		
					'hover8' => __( 'Text from right', 'portfolio-elementor' ),	
					'hover9' => __( 'Text from Top', 'portfolio-elementor' ),		
					'hover10' => __( 'Text from Bottom', 'portfolio-elementor' ),
					'hover11' => __( 'Zoom Out', 'portfolio-elementor' ),		
					'hover12' => __( 'Card from Left', 'portfolio-elementor' ),	
					'hover13' => __( 'Card from Right', 'portfolio-elementor' ),	
					'hover14' => __( 'Card from Bottom', 'portfolio-elementor' ),
				]
			]
		);		

	

		$this->end_controls_section();

		$this->start_controls_section(
			'section_item_description',
			[
				'label' => __( 'Item', 'portfolio-elementor' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		//Hover: Background color

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'bgcolor',
				'label' => __( 'Hover: Background Color', 'portfolio-elementor' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .portfolio-item-infos-wrapper',
			]
		);

		

		//Text Transform
		$this->add_control(
			'text_transform',
			[
				'label' => __( 'Item Description: Text Transform', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => __( 'None', 'portfolio-elementor' ),
					'uppercase' => __( 'UPPERCASE', 'portfolio-elementor' ),
					'lowercase' => __( 'lowercase', 'portfolio-elementor' ),
					'capitalize' => __( 'Capitalize', 'portfolio-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .portfolio-item-infos-wrapper' => 'text-transform: {{VALUE}};',
				],
			]
		);

		//Text Aligment
		$this->add_control(
			'text_align',
			[
				'label' => __( 'Item Description: Text Align', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'center' => __( 'Center', 'portfolio-elementor' ),
					'left' => __( 'Left', 'portfolio-elementor' ),
					'right' => __( 'Right', 'portfolio-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .portfolio-item-infos-wrapper' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'vertical_align',
			[
				'label' => __( 'Item Description: Vertical Align', 'portfolio-elementor' ),
				'type' => Controls_Manager::SELECT,
				'default' => '50%',
				'options' => [
					'60px' => __( 'Top', 'portfolio-elementor' ),
					'50%' => __( 'Center', 'portfolio-elementor' ),
					'70%' => __( 'Bottom', 'portfolio-elementor' ),
				],
				'selectors' => [
					'{{WRAPPER}} .elpt-portfolio-content .portfolio-item-infos' => 'top: {{VALUE}};',
				],
			]
		);

		//Border Radius
		$this->add_control(
			'border_radius',
			[
				'label' => __( 'Item: Border Radius', 'portfolio-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 0,
				],
				'selectors' => [
					'{{WRAPPER}} .elpt-portfolio-content .portfolio-item' => 'border-radius: {{SIZE}}{{UNIT}};',
					//'{{WRAPPER}} .elpt-portfolio-content .portfolio-item img' => 'border-radius: {{SIZE}}{{UNIT}};',
				],
			]
		);
		

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings();		

		//$style = $settings['Portfolio_carousel_style'];

		//$carousellist = $this->get_settings( 'Portfolio_carousel' );

		?>

		<?php echo do_shortcode('[portfolio-carousel hover="'.esc_attr($settings['hover']).'" post_type="'.esc_attr($settings['post_type']).'" type="'.esc_attr($settings['type']) .'" taxonomy="'.esc_attr($settings['taxonomy']).'" linkto="'.esc_attr($settings['linkto']).'"]'); ?>

		<?php wp_reset_postdata(); ?>
	

		<?php
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	/*protected function _content_template() {
		
	}*/
}