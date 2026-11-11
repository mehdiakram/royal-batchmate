<?php
if ( ! defined( 'ABSPATH' ) ) exit; // ?????? ????????? ????

/**
 * BatchMate Latest Members Widget Class
 */
class BatchMate_Latest_Members_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'BatchMate_latest_members';
    }

    public function get_title() {
        return 'BatchMate Latest Members';
    }

    public function get_icon() {
        return 'eicon-person';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        
        // --- CONTENT TAB: Layout Settings ---
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Layout Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'limit',
            [
                'label' => 'Number of Members to Show',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'default' => 8,
                'description' => 'e.g. 8, 12, 16',
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => 'Columns',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '4',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1 Column',
                    '2' => '2 Columns',
                    '3' => '3 Columns',
                    '4' => '4 Columns',
                    '5' => '5 Columns',
                    '6' => '6 Columns',
                ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-latest-members-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => 'Gap',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [ 'min' => 0, 'max' => 100 ],
                ],
                'default' => [ 'size' => 20, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-latest-members-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();


        // --- STYLE TAB: Card Style ---
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => 'Card Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg_color',
            [
                'label' => 'Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-widget-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .BatchMate-widget-card',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-widget-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => 8, 'right' => 8, 'bottom' => 8, 'left' => 8, 'unit' => 'px',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .BatchMate-widget-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-widget-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20, 'unit' => 'px',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'card_text_align',
            [
                'label' => 'Alignment',
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [ 'title' => 'Left', 'icon' => 'eicon-text-align-left' ],
                    'center' => [ 'title' => 'Center', 'icon' => 'eicon-text-align-center' ],
                    'right' => [ 'title' => 'Right', 'icon' => 'eicon-text-align-right' ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-widget-card' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();


        // --- STYLE TAB: Image Style ---
        $this->start_controls_section(
            'section_style_image',
            [
                'label' => 'Image',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_width',
            [
                'label' => 'Width',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [ 'min' => 50, 'max' => 300 ],
                ],
                'default' => [ 'size' => 120, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-photo' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-photo' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .BatchMate-member-photo',
            ]
        );

        $this->add_responsive_control(
            'image_margin_bottom',
            [
                'label' => 'Margin Bottom',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [ 'size' => 15, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-photo' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();


        // --- STYLE TAB: Typography & Content ---
        $this->start_controls_section(
            'section_style_content',
            [
                'label' => 'Content',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Name
        $this->add_control( 'heading_name', [ 'label' => 'Name', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        
        $this->add_control(
            'name_color',
            [
                'label' => 'Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-name' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'name_hover_color',
            [
                'label' => 'Hover Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-name:hover' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'name_typography',
                'selector' => '{{WRAPPER}} .BatchMate-member-name',
            ]
        );

        // Program
        $this->add_control( 'heading_program', [ 'label' => 'Program', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        
        $this->add_control(
            'program_color',
            [
                'label' => 'Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-program' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'program_typography',
                'selector' => '{{WRAPPER}} .BatchMate-member-program',
            ]
        );

        // Batch
        $this->add_control( 'heading_batch', [ 'label' => 'Batch', 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before' ] );
        
        $this->add_control(
            'batch_color',
            [
                'label' => 'Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#777777',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-member-batch' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'batch_typography',
                'selector' => '{{WRAPPER}} .BatchMate-member-batch',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        global $wpdb;
        $settings = $this->get_settings_for_display();
        $limit = $settings['limit'];

        // ??????? ???
        $member_table = $wpdb->prefix . 'batchmate_member';
        $prog_table = $wpdb->prefix . 'batchmate_program';
        
        // ??????? ?????? (??????? $limit ??)
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT m.member_number, m.name, m.photo_url, m.present_district 
             FROM $member_table m
             ORDER BY m.id DESC 
             LIMIT %d",
            $limit
        ) );

        if ( $results ) {
            $default_photo = ROYAL_BATCHMATE_PLUGIN_URL . '/assets/images/noprofile.png';

            // Check if mobile/tablet columns are explicitly set, if not, provide a fallback CSS
            $mobile_cols = isset($settings['columns_mobile']) && $settings['columns_mobile'] !== '' ? $settings['columns_mobile'] : 1;
            $tablet_cols = isset($settings['columns_tablet']) && $settings['columns_tablet'] !== '' ? $settings['columns_tablet'] : 2;

            echo '<style>
                .BatchMate-member-photo {
                    max-width: 100%;
                    height: auto !important;
                    aspect-ratio: 1 / 1;
                }
                @media (max-width: 767px) {
                    .elementor-widget-BatchMate_latest_members .BatchMate-latest-members-grid {
                        grid-template-columns: repeat(' . esc_attr($mobile_cols) . ', 1fr) !important;
                    }
                }
                @media (min-width: 768px) and (max-width: 1024px) {
                    .elementor-widget-BatchMate_latest_members .BatchMate-latest-members-grid {
                        grid-template-columns: repeat(' . esc_attr($tablet_cols) . ', 1fr) !important;
                    }
                }
            </style>';

            echo '<div class="BatchMate-latest-members-grid" style="display: grid; width: 100%;">';
            
            foreach ( $results as $member ) {
                $photo = !empty($member->photo_url) ? $member->photo_url : $default_photo;
                
                $link = home_url( '/member/' . strtolower($member->member_number) . '/' );
                
                ?>
                <div class="BatchMate-widget-card">
                    <a href="<?php echo esc_url($link); ?>" style="display: block;">
                        <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($member->name); ?>" class="BatchMate-member-photo" style="object-fit: cover;">
                    </a>
                    <h3 style="margin: 0 0 5px;">
                        <a href="<?php echo esc_url($link); ?>" class="BatchMate-member-name" style="text-decoration: none; transition: color 0.3s;">
                            <?php echo esc_html($member->name); ?>
                        </a>
                    </h3>
                    <p class="BatchMate-member-program" style="margin: 0; font-weight: 500;">
                        <?php echo !empty($member->present_district) ? 'জেলা: ' . esc_html($member->present_district) : ''; ?>
                    </p>
                    
                </div>
                <?php
            }
            
            echo '</div>';
        } else {
            echo '<div style="text-align:center; padding:20px;">No members found.</div>';
        }
    }
}