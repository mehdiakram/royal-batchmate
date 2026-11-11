<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * BatchMate Event Form Elementor Widget
 * Elementor-এ ড্র্যাগ করে ব্যবহার করুন — header রং, সার্চ বক্স স্টাইল সহ সব কিছু কাস্টমাইজ করা যাবে।
 */
class BatchMate_Event_Form_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'batchmate_event_form';
    }

    public function get_title() {
        return 'BatchMate Event Form';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    public function get_keywords() {
        return [ 'event', 'registration', 'form', 'batchmate', 'BatchMate' ];
    }

    protected function register_controls() {

        // ── CONTENT TAB ─────────────────────────────────────────────────
        $this->start_controls_section(
            'section_content',
            [
                'label' => 'Event Settings',
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Build event list for dropdown
        global $wpdb;
        $events_raw = $wpdb->get_results(
            "SELECT id, event_name FROM {$wpdb->prefix}batchmate_event WHERE is_active = 1 ORDER BY id DESC LIMIT 50"
        );
        $event_options = [ '' => '— Select an Event —' ];
        if ( $events_raw ) {
            foreach ( $events_raw as $ev ) {
                $event_options[ $ev->id ] = $ev->event_name;
            }
        }

        $this->add_control(
            'event_id',
            [
                'label'       => 'Select Event',
                'type'        => \Elementor\Controls_Manager::SELECT,
                'options'     => $event_options,
                'default'     => '',
                'description' => 'রেজিস্ট্রেশন ফর্মটি যে ইভেন্টের জন্য দেখাতে চান তা সিলেক্ট করুন।',
            ]
        );

        $this->end_controls_section();

        // ── STYLE TAB — Header ───────────────────────────────────────────
        $this->start_controls_section(
            'section_style_header',
            [
                'label' => '🎨 Header Style',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'header_bg_type',
            [
                'label'   => 'Background Type',
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'gradient' => 'Gradient (Default Blue)',
                    'solid'    => 'Solid Color',
                    'custom'   => 'Custom Gradient',
                ],
                'default' => 'gradient',
            ]
        );

        $this->add_control(
            'header_bg_color',
            [
                'label'     => 'Solid Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1e3a8a',
                'condition' => [ 'header_bg_type' => 'solid' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-header' => 'background: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'header_gradient_from',
            [
                'label'     => 'Gradient From (Left)',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'condition' => [ 'header_bg_type' => 'custom' ],
            ]
        );

        $this->add_control(
            'header_gradient_mid',
            [
                'label'     => 'Gradient Middle',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#1e3a8a',
                'condition' => [ 'header_bg_type' => 'custom' ],
            ]
        );

        $this->add_control(
            'header_gradient_to',
            [
                'label'     => 'Gradient To (Right)',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'condition' => [ 'header_bg_type' => 'custom' ],
            ]
        );

        $this->add_control(
            'header_gradient_angle',
            [
                'label'     => 'Gradient Angle',
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [ 'deg' => [ 'min' => 0, 'max' => 360 ] ],
                'default'   => [ 'size' => 135, 'unit' => 'deg' ],
                'condition' => [ 'header_bg_type' => 'custom' ],
            ]
        );

        $this->add_control(
            'header_text_color',
            [
                'label'     => 'Text / Title Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-title'       => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .BatchMate-ef-badge-event' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .BatchMate-ef-pill'        => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'header_padding',
            [
                'label'      => 'Header Padding',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em' ],
                'default'    => [ 'top' => 28, 'right' => 32, 'bottom' => 24, 'left' => 32, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .BatchMate-ef-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();

        // ── STYLE TAB — Search Box ───────────────────────────────────────
        $this->start_controls_section(
            'section_style_search',
            [
                'label' => '🔍 Search Box Style',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'search_bg_color',
            [
                'label'     => 'Search Area Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-search-box' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'input_border_color',
            [
                'label'     => 'Input Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#94a3b8',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-input' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'input_border_width',
            [
                'label'     => 'Input Border Width',
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [ 'px' => [ 'min' => 1, 'max' => 5 ] ],
                'default'   => [ 'size' => 2, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-input' => 'border-width: {{SIZE}}{{UNIT}} !important; border-style: solid !important;',
                ],
            ]
        );

        $this->add_control(
            'input_focus_color',
            [
                'label'     => 'Input Focus Border Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-input:focus' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'input_border_radius',
            [
                'label'     => 'Input Border Radius',
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
                'default'   => [ 'size' => 10, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-input' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'input_bg_color',
            [
                'label'     => 'Input Background Color',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-input' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'label'    => 'Input Text Typography',
                'selector' => '{{WRAPPER}} .BatchMate-ef-input',
            ]
        );

        $this->end_controls_section();

        // ── STYLE TAB — Card / Wrapper ──────────────────────────────────
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => '📦 Outer Card Style',
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_max_width',
            [
                'label'     => 'Max Width',
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [ 'px' => [ 'min' => 400, 'max' => 1400 ] ],
                'default'   => [ 'size' => 900, 'unit' => 'px' ],
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-wrap' => 'max-width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'card_bg_color',
            [
                'label'     => 'Card Background',
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .BatchMate-ef-wrap' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'label'    => 'Card Border',
                'selector' => '{{WRAPPER}} .BatchMate-ef-wrap',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label'      => 'Border Radius',
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'default'    => [ 'top' => 18, 'right' => 18, 'bottom' => 18, 'left' => 18, 'unit' => 'px' ],
                'selectors'  => [
                    '{{WRAPPER}} .BatchMate-ef-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .BatchMate-ef-wrap',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $event_id = intval( $settings['event_id'] ?? 0 );

        if ( ! $event_id ) {
            echo '<div style="padding:20px; background:#fff3cd; border:1px solid #ffc107; border-radius:8px; color:#856404;">'
               . '⚠️ <strong>BatchMate Event Form:</strong> Please select an event from the widget settings (Content tab).'
               . '</div>';
            return;
        }

        // ── Shortcode আগে render করি (এর নিজস্ব <style> tag আসে) ──
        $shortcode_html = do_shortcode( '[event_form id="' . $event_id . '"]' );
        echo $shortcode_html;

        // ── এরপর override CSS দিই — এটা shortcode CSS এর পরে থাকায় জিতবে ──
        $widget_id      = $this->get_id();
        $wrapper_sel    = '#' . esc_attr( 'e-' . $widget_id ) . ' .BatchMate-ef-header, '
                        . '.elementor-element-' . esc_attr( $widget_id ) . ' .BatchMate-ef-header';

        $header_bg_type = $settings['header_bg_type'] ?? 'gradient';

        $header_css = '';

        if ( $header_bg_type === 'solid' ) {
            $color      = esc_attr( $settings['header_bg_color'] ?? '#1e3a8a' );
            $header_css = $wrapper_sel . ' { background: ' . $color . ' !important; }';

        } elseif ( $header_bg_type === 'custom' ) {
            $from  = esc_attr( $settings['header_gradient_from']           ?? '#0f172a' );
            $mid   = esc_attr( $settings['header_gradient_mid']            ?? '#1e3a8a' );
            $to    = esc_attr( $settings['header_gradient_to']             ?? '#2563eb' );
            $angle = intval(   $settings['header_gradient_angle']['size']  ?? 135 );
            $header_css = $wrapper_sel . ' { background: linear-gradient('
                        . $angle . 'deg, ' . $from . ' 0%, ' . $mid . ' 52%, ' . $to
                        . ' 100%) !important; }';
        }
        // 'gradient' (default) — shortcode CSS যথেষ্ট, override দরকার নেই

        if ( $header_css ) {
            echo '<style id="bm-ef-override-' . esc_attr( $widget_id ) . '">' . $header_css . '</style>';
        }
    }
}
