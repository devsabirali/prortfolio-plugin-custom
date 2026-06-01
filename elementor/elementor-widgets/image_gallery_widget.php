<?php

namespace Powerfolio\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
/**
 *
 * @since 1.0.0
 */
class ELPT_Image_Gallery_Widget extends Widget_Base {
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
        return 'powerfolio_gallery';
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
        return __( 'Image Gallery (Powerfolio)', 'portfolio-elementor' );
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
        return 'eicon-gallery-justified';
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
        return ['elpug-elements'];
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
        return ['elpug'];
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
    protected function register_controls() {
        $pro_version = true;
        //pe_fs()->can_use_premium_code__premium_only();
        //=========== Main Settings	==============
        $this->start_controls_section( 'section_content', [
            'label' => __( 'General Settings', 'portfolio-elementor' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        //======= Gallery ============
        $repeater = new \Elementor\Repeater();
        $repeater->add_control( 'list_title', [
            'label'       => __( 'Title', 'portfolio-elementor' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'List Title', 'portfolio-elementor' ),
            'label_block' => true,
        ] );
        $repeater->add_control( 'list_filter_tag', [
            'label'       => __( 'Tag (To use with filter)', 'portfolio-elementor' ),
            'type'        => Controls_Manager::TEXT,
            'description' => 'You can add several tags by adding a comma between each tag',
            'default'     => __( 'Tag 1', 'portfolio-elementor' ),
            'label_block' => true,
        ] );
        $repeater->add_control( 'list_image', [
            'label'   => __( 'Choose Image', 'portfolio-elementor' ),
            'type'    => Controls_Manager::MEDIA,
            'default' => [
                'url' => \Elementor\Utils::get_placeholder_image_src(),
            ],
        ] );
        $repeater->add_control( 'list_hover_image', [
            'label'       => __( 'Hover Image (Optional)', 'portfolio-elementor' ),
            'type'        => Controls_Manager::MEDIA,
            'description' => __( 'Image to display on hover. Perfect for GIFs or alternative images. If not set, no hover effect will be applied.', 'portfolio-elementor' ),
            'default'     => [],
        ] );
        $repeater->add_control( 'list_icon', [
            'label'       => __( 'Icon (Optional)', 'portfolio-elementor' ),
            'type'        => \Elementor\Controls_Manager::ICONS,
            'default'     => [],
            'label_block' => false,
            'description' => __( 'Optional icon displayed with the item info.', 'portfolio-elementor' ),
        ] );
        // END - PRO Version Snippet
        $this->add_control( 'list', [
            'label'       => __( 'Gallery Items', 'portfolio-elementor' ),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [[
                'list_title'   => __( 'Title #1', 'portfolio-elementor' ),
                'list_content' => __( 'Item content. Click the edit button to change this text.', 'portfolio-elementor' ),
            ], [
                'list_title'   => __( 'Title #2', 'portfolio-elementor' ),
                'list_content' => __( 'Item content. Click the edit button to change this text.', 'portfolio-elementor' ),
            ]],
            'title_field' => '{{{ list_title }}}',
        ] );
        // ====== END Gallery =========
        $showfilter_description = '';
        $this->add_control( 'showfilter', [
            'label'   => __( 'Show category filter?', 'portfolio-elementor' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'yes',
            'options' => \Powerfolio_Common_Settings::get_yes_no_options(),
        ] );
        $this->add_control( 'showallbtn', [
            'label'       => __( 'Show "All" option?', 'portfolio-elementor' ),
            'description' => $showfilter_description,
            'type'        => Controls_Manager::SELECT,
            'default'     => 'yes',
            'condition'   => [
                'showfilter' => 'yes',
            ],
            'options'     => \Powerfolio_Common_Settings::get_yes_no_options(),
        ] );
        $this->add_control( 'tax_text', [
            'label'     => __( 'All Categories - Button Text', 'portfolio-elementor' ),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __( 'All', 'portfolio-elementor' ),
            'condition' => [
                'showfilter' => 'yes',
            ],
        ] );
        $this->add_control( 'Upgrade_note3', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
        $this->end_controls_section();
        //=========== END - Main Settings	==============
        //=========== Grid Settings	==============
        $this->start_controls_section( 'section_grid', [
            'label' => __( 'Grid Settings', 'portfolio-elementor' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $description = __( 'Upgrade your plan to get access to the special grids and also to the Grid Builder - Our exclusive feature! <a href="https://checkout.freemius.com/mode/dialog/plugin/7226/plan/12571/">CLICK TO UPGRADE</a>', 'portfolio-elementor' );
        //Style
        $this->add_control( 'style', [
            'label'       => __( 'Grid Style', 'portfolio-elementor' ),
            'type'        => Controls_Manager::SELECT,
            'default'     => 'box',
            'description' => $description,
            'options'     => \Powerfolio_Common_Settings::get_grid_options(),
        ] );
        //columns
        $this->add_control( 'columns', [
            'label'      => __( 'Number of columns', 'portfolio-elementor' ),
            'type'       => Controls_Manager::SELECT,
            'default'    => '3',
            'conditions' => array(
                'relation' => 'or',
                'terms'    => array(array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'box',
                ), array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'masonry',
                )),
            ),
            'options'    => \Powerfolio_Common_Settings::get_column_options(),
        ] );
        $margin_description = '';
        $this->add_control( 'margin', [
            'label'        => __( 'Use item margin?', 'portfolio-elementor' ),
            'description'  => $margin_description,
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
            'conditions'   => array(
                'relation' => 'or',
                'terms'    => array(array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'box',
                ), array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'masonry',
                ), array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'grid_builder',
                )),
            ),
        ] );
        //Margin Size
        $this->add_control( 'margin_size', [
            'label'      => __( 'Additional Margin (px)', 'portfolio-elementor' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'conditions' => array(
                'relation' => 'or',
                'terms'    => array(array(
                    'name'     => 'margin',
                    'operator' => '==',
                    'value'    => 'yes',
                )),
            ),
            'range'      => [
                'px' => [
                    'min'  => 0,
                    'max'  => 20,
                    'step' => 1,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 0,
            ],
            'selectors'  => [
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-margin .portfolio-item-wrapper' => 'padding-right: calc(5px + {{SIZE}}{{UNIT}}); padding-left: calc(5px + {{SIZE}}{{UNIT}}); padding-bottom: calc((5px + {{SIZE}}{{UNIT}})*2);',
            ],
        ] );
        //================================== GRID BUILDER ========================
        // Note: Image Gallery uses a Repeater for items, so we show all 25 controls
        // when grid_builder is active. Users control the number of items via the Repeater.
        for ($i = 1; $i <= 25; $i++) {
            //width
            $item = 'item_' . $i;
            $this->add_control( $item . '_popover_toggle', [
                'label'        => sprintf( __( 'Item %d', 'portfolio-elementor' ), $i ),
                'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
                'label_off'    => __( 'Default', 'portfolio-elementor' ),
                'label_on'     => __( 'Custom', 'portfolio-elementor' ),
                'return_value' => 'yes',
                'conditions'   => array(
                    'relation' => 'and',
                    'terms'    => array(array(
                        'name'     => 'style',
                        'operator' => '==',
                        'value'    => 'grid_builder',
                    )),
                ),
            ] );
            $this->start_popover();
            $this->add_responsive_control( $item, [
                'label'      => __( 'Width', 'portfolio-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['%', 'px'],
                'default'    => [
                    'unit' => '%',
                    'size' => 25,
                ],
                'range'      => [
                    '%'  => [
                        'min'  => 10,
                        'max'  => 100,
                        'step' => 5,
                    ],
                    'px' => [
                        'min'  => 50,
                        'max'  => 1200,
                        'step' => 10,
                    ],
                ],
                'conditions' => array(
                    'relation' => 'and',
                    'terms'    => array(array(
                        'name'     => 'style',
                        'operator' => '==',
                        'value'    => 'grid_builder',
                    )),
                ),
                'selectors'  => [
                    '{{WRAPPER}} .elpt-portfolio-content .portfolio-item-wrapper:nth-child(' . $i . ')'                      => 'width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elpt-portfolio-content.elpt-fixed-layout-mode .portfolio-item-wrapper.elpt-grid-pos-' . $i => 'width: {{SIZE}}{{UNIT}};',
                ],
            ] );
            //height
            $itemh = 'item_height_' . $i;
            $this->add_responsive_control( $itemh, [
                'label'      => __( 'Height (px)', 'portfolio-elementor' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'default'    => [
                    'unit' => 'px',
                    'size' => 280,
                ],
                'range'      => [
                    'px' => [
                        'min'  => 20,
                        'max'  => 840,
                        'step' => 20,
                    ],
                ],
                'conditions' => array(
                    'relation' => 'and',
                    'terms'    => array(array(
                        'name'     => 'style',
                        'operator' => '==',
                        'value'    => 'grid_builder',
                    )),
                ),
                'selectors'  => [
                    '{{WRAPPER}} .elpt-portfolio-content .portfolio-item-wrapper:nth-child(' . $i . ') a'                           => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .elpt-portfolio-content.elpt-fixed-layout-mode .portfolio-item-wrapper.elpt-grid-pos-' . $i . ' a' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ] );
            //padding (all sides using Elementor's native dimensions control)
            $item_padding = 'item_padding_' . $i;
            $this->add_responsive_control( $item_padding, [
                'label'       => __( 'Padding', 'portfolio-elementor' ),
                'type'        => Controls_Manager::DIMENSIONS,
                'size_units'  => ['px', '%'],
                'conditions'  => array(
                    'relation' => 'and',
                    'terms'    => array(array(
                        'name'     => 'style',
                        'operator' => '==',
                        'value'    => 'grid_builder',
                    )),
                ),
                'selectors'   => [
                    '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-grid-builder .portfolio-item-wrapper:nth-child(' . $i . ')'                      => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-grid-builder.elpt-fixed-layout-mode .portfolio-item-wrapper.elpt-grid-pos-' . $i => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'description' => __( 'Add spacing inside the item wrapper. Use to create margins between the item borders and content.', 'portfolio-elementor' ),
            ] );
            $this->end_popover();
        }
        //================================== END OF GRID BUILDER==================
        //Fixed Layout Mode - Maintain positions on filter
        $this->add_control( 'grid_fixed_layout', [
            'label'       => __( 'Fixed Layout', 'portfolio-elementor' ),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => __( 'Yes', 'portfolio-elementor' ),
            'label_off'   => __( 'No', 'portfolio-elementor' ),
            'description' => __( 'When enabled, items maintain their positions when filtered, leaving empty spaces instead of reorganizing.', 'portfolio-elementor' ),
            'condition'   => [
                'style' => 'grid_builder',
            ],
            'separator'   => 'before',
        ] );
        //Box Height
        $this->add_control( 'box_height', [
            'label'      => __( 'Box Height (px)', 'portfolio-elementor' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'conditions' => array(
                'relation' => 'or',
                'terms'    => array(array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'box',
                ), array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'specialgrid5',
                ), array(
                    'name'     => 'style',
                    'operator' => '==',
                    'value'    => 'specialgrid6',
                )),
            ),
            'range'      => [
                'px' => [
                    'min'  => 10,
                    'max'  => 800,
                    'step' => 1,
                ],
            ],
            'default'    => [
                'unit' => 'px',
                'size' => 250,
            ],
            'selectors'  => [
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-style-box .portfolio-item'              => 'height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-special-grid-5 .portfolio-item-wrapper' => 'height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-special-grid-5 .portfolio-item'         => 'height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-special-grid-6 .portfolio-item-wrapper' => 'height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .elpt-portfolio-content.elpt-portfolio-special-grid-6 .portfolio-item'         => 'height: {{SIZE}}{{UNIT}};',
            ],
        ] );
        $this->add_control( 'Upgrade_note2', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
        $this->end_controls_section();
        //=========== END - Grid Settings	==============
        //=========== Hover Settings	==============
        $this->start_controls_section( 'section_hover', [
            'label' => __( 'Hover Settings', 'portfolio-elementor' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $description = __( 'Upgrade your plan to get access to 15+ hover effects! <a href="https://checkout.freemius.com/mode/dialog/plugin/7226/plan/12571/">CLICK TO UPGRADE</a>', 'portfolio-elementor' );
        $this->add_control( 'hover', [
            'label'   => __( 'Hover Style', 'portfolio-elementor' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'simple',
            'options' => \Powerfolio_Common_Settings::get_hover_options(),
        ] );
        $this->add_control( 'Upgrade_note', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
        //=========== END - Grid Settings	==============
        $this->end_controls_section();
        //=========== ADVANCED SECTION	==============
        $this->start_controls_section( 'section_advanced', [
            'label' => __( 'Advanced', 'portfolio-elementor' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'Upgrade_note4', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
        $this->end_controls_section();
        //=========== END - ADVANCED SECTION	==============
        //==========================================================================================
        $this->start_controls_section( 'section_item_description', [
            'label' => __( 'Item', 'portfolio-elementor' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );
        //Hover: Background color
        $this->add_group_control( \Elementor\Group_Control_Background::get_type(), [
            'name'     => 'bgcolor',
            'label'    => __( 'Hover: Background Color', 'portfolio-elementor' ),
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .portfolio-item-infos-wrapper',
        ] );
        $this->add_control( 'Upgrade_note6', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
        // END - PRO Version Snippets
        $this->end_controls_section();
        $this->start_controls_section( 'section_style', [
            'label' => __( 'Filter', 'portfolio-elementor' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );
        $this->add_control( 'Upgrade_note7', [
            'label'           => '',
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => \Powerfolio_Common_Settings::get_upgrade_message( 'elementor' ),
            'content_classes' => 'your-class',
        ] );
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
        // Load Template
        $settings = $this->get_settings();
        $image_gallery = new \Powerfolio_Image_Gallery();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped within the method
        echo $image_gallery->get_image_gallery_template( $settings );
        ?>
		<?php 
    }

}
