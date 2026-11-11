<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class BatchMate_Member_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'batchmate_member_widget';
	}

	public function get_title() {
		return esc_html__( 'BatchMate Member', 'BatchMate' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return [ 'general' ];
	}

	public function get_keywords() {
		return [ 'member', 'BatchMate', 'profile' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'BatchMate' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'member_number',
			[
				'label' => esc_html__( 'Member Number', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'BM-00001',
				'placeholder' => esc_html__( 'e.g. BM-00001', 'BatchMate' ),
			]
		);

		$this->add_control(
			'show_photo',
			[
				'label' => esc_html__( 'Show Photo', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_name',
			[
				'label' => esc_html__( 'Show Name', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_dept',
			[
				'label' => esc_html__( 'Show District', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_phone',
			[
				'label' => esc_html__( 'Show Cell Number', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_position',
			[
				'label' => esc_html__( 'Show Designation', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'show_ec_post',
			[
				'label' => esc_html__( 'Show EC Post (If Any)', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'BatchMate' ),
				'label_off' => esc_html__( 'Hide', 'BatchMate' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->end_controls_section();
		
		$this->start_controls_section(
			'style_section',
			[
				'label' => esc_html__( 'Card Style', 'BatchMate' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);
		
		$this->add_control(
			'text_align',
			[
				'label' => esc_html__( 'Alignment', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'BatchMate' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'BatchMate' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'BatchMate' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'default' => 'center',
				'selectors' => [
					'{{WRAPPER}} .BatchMate-member-card' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_bg_color',
			[
				'label' => esc_html__( 'Card Background Color', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .BatchMate-member-card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'card_border_color',
			[
				'label' => esc_html__( 'Card Border Color', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .BatchMate-member-card' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Name
		$this->start_controls_section(
			'style_name_section',
			[
				'label' => esc_html__( 'Name Style', 'BatchMate' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'name_color',
			[
				'label' => esc_html__( 'Name Color', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .BatchMate-mc-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'name_typography',
				'selector' => '{{WRAPPER}} .BatchMate-mc-name',
			]
		);

		$this->end_controls_section();

		// Style: Info (Dept, Phone, Position)
		$this->start_controls_section(
			'style_info_section',
			[
				'label' => esc_html__( 'Text Info Style', 'BatchMate' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'info_color',
			[
				'label' => esc_html__( 'Text Color', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .BatchMate-mc-dept, {{WRAPPER}} .BatchMate-mc-phone, {{WRAPPER}} .BatchMate-mc-position' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'info_typography',
				'selector' => '{{WRAPPER}} .BatchMate-mc-dept, {{WRAPPER}} .BatchMate-mc-phone, {{WRAPPER}} .BatchMate-mc-position',
			]
		);

		$this->end_controls_section();

		// Style: EC Post
		$this->start_controls_section(
			'style_ec_section',
			[
				'label' => esc_html__( 'EC Post Style', 'BatchMate' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ec_post_color',
			[
				'label' => esc_html__( 'EC Post Color', 'BatchMate' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .BatchMate-mc-ec-post' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'ec_post_typography',
				'selector' => '{{WRAPPER}} .BatchMate-mc-ec-post',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$member_number = sanitize_text_field( $settings['member_number'] );
		
		if ( empty( $member_number ) ) {
		    echo '<div class="BatchMate-member-card"><p>Please enter a Member Number in widget settings.</p></div>';
		    return;
		}

		global $wpdb;
		$member = $wpdb->get_row( $wpdb->prepare(
			"SELECT m.* 
			 FROM {$wpdb->prefix}batchmate_member m 
			 WHERE m.member_number = %s",
			$member_number
		) );

		if ( ! $member ) {
			echo '<div class="BatchMate-member-card"><p>Member not found: ' . esc_html( $member_number ) . '</p></div>';
			return;
		}
		
		$ec_post_title = '';
		if ( 'yes' === $settings['show_ec_post'] ) {
			$ec_post_title = $wpdb->get_var( $wpdb->prepare(
				"SELECT pt.post_title 
				 FROM {$wpdb->prefix}batchmate_ec ec
				 JOIN {$wpdb->prefix}batchmate_committee c ON ec.committee_id = c.id
				 JOIN {$wpdb->prefix}batchmate_ec_post_title pt ON ec.post_id = pt.id
				 WHERE ec.member_id = %d AND c.is_active = 1
				 LIMIT 1",
				$member->id
			) );
		}

		$profile_url = home_url( '/member/' . strtolower( $member->member_number ) . '/' );
		
		// Modern UI Card CSS
		echo '<style>
			.BatchMate-member-card {
				background: #ffffff;
				border-radius: 16px;
				padding: 30px 20px;
				box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
				transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
				border: 1px solid rgba(0,0,0,0.03);
				position: relative;
				overflow: hidden;
				display: flex;
				flex-direction: column;
				align-items: center;
			}
			.BatchMate-member-card:hover {
				transform: translateY(-5px);
				box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
				border-color: rgba(0,0,0,0.08);
			}
			.BatchMate-mc-photo img {
				width: 130px;
				height: 130px;
				object-fit: cover;
				border-radius: 50%;
				border: 4px solid #fff;
				box-shadow: 0 8px 16px rgba(0,0,0,0.1);
				margin-bottom: 20px;
				transition: transform 0.4s ease;
			}
			.BatchMate-member-card:hover .BatchMate-mc-photo img {
				transform: scale(1.05);
			}
			.BatchMate-mc-name {
				margin: 0 0 8px 0;
				color: #1a1a5c;
				font-size: 1.35em;
				font-weight: 700;
				letter-spacing: -0.3px;
			}
			.BatchMate-mc-ec-post {
				margin-bottom: 12px;
				color: #e53935;
				font-weight: 700;
				font-size: 0.9em;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				background: rgba(229, 57, 53, 0.1);
				padding: 4px 12px;
				border-radius: 20px;
				display: inline-block;
			}
			.BatchMate-mc-dept, .BatchMate-mc-phone, .BatchMate-mc-position {
				color: #64748b;
				font-size: 0.95em;
				margin-bottom: 6px;
				line-height: 1.5;
			}
			.BatchMate-mc-position {
				font-weight: 600;
				color: #334155;
			}
		</style>';

		// Render Card
		?>
		<a href="<?php echo esc_url( $profile_url ); ?>" style="text-decoration: none; color: inherit; display: block; max-width: 100%;">
			<div class="BatchMate-member-card">
				<?php if ( 'yes' === $settings['show_photo'] ) : 
					$photo_url = !empty($member->photo_url) ? esc_url($member->photo_url) : 'https://via.placeholder.com/400?text=No+Photo';
				?>
					<div class="BatchMate-mc-photo">
						<img src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr( $member->name ); ?>">
					</div>
				<?php endif; ?>
				
				<?php if ( 'yes' === $settings['show_ec_post'] && !empty($ec_post_title) ) : ?>
					<div class="BatchMate-mc-ec-post">
						<?php echo esc_html( $ec_post_title ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_name'] ) : ?>
					<h4 class="BatchMate-mc-name">
						<?php echo esc_html( $member->name ); ?>
					</h4>
				<?php endif; ?>
				
				<?php if ( 'yes' === $settings['show_position'] && !empty($member->employment_position) ) : ?>
					<div class="BatchMate-mc-position">
						<?php echo esc_html( $member->employment_position ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $settings['show_dept'] && !empty($member->present_district) ) : ?>
					<div class="BatchMate-mc-dept">
						জেলা: <?php echo esc_html( $member->present_district ); ?> 
					</div>
				<?php endif; ?>
				
				<?php if ( 'yes' === $settings['show_phone'] && !empty($member->cell_number) ) : ?>
					<div class="BatchMate-mc-phone">
						<?php echo esc_html( $member->cell_number ); ?>
					</div>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}
}
