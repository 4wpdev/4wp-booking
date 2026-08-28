<?php
/**
 * Elementor booking widget.
 *
 * @package ForWP\Booking
 */

namespace ForWP\Booking;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the calendar template inside Elementor.
 */
final class Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'forwp-booking';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( '4WP Booking', '4wp-booking' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-calendar';
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords(): array {
		return array( 'booking', 'calendar', 'appointment', '4wp' );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$this->start_controls_section(
			'forwp_booking_section',
			array(
				'label' => __( 'Booking', '4wp-booking' ),
			)
		);

		$providers = array();
		foreach ( Provider_Registry::implemented_slugs() as $slug ) {
			$item = Provider_Registry::get( $slug );
			if ( $item ) {
				$providers[ $slug ] = $item->get_label();
			}
		}

		$this->add_control(
			'provider',
			array(
				'label'   => __( 'Provider', '4wp-booking' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => __( 'Default (plugin settings)', '4wp-booking' ) ) + $providers,
			)
		);

		$templates = array();
		foreach ( Template_Registry::all() as $tpl ) {
			$templates[ $tpl->get_slug() ] = $tpl->get_label();
		}

		$this->add_control(
			'template',
			array(
				'label'   => __( 'Template', '4wp-booking' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array( '' => __( 'Default (plugin settings)', '4wp-booking' ) ) + $templates,
			)
		);

		$this->add_control(
			'flow',
			array(
				'label'   => __( 'Start with', '4wp-booking' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''      => __( 'Default (plugin settings)', '4wp-booking' ),
					'staff'   => __( 'Doctor list', '4wp-booking' ),
					'date'    => __( 'Calendar date', '4wp-booking' ),
					'service' => __( 'Service list', '4wp-booking' ),
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Front render.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$html     = Plugin::render(
			array(
				'provider' => isset( $settings['provider'] ) ? (string) $settings['provider'] : '',
				'template' => isset( $settings['template'] ) ? (string) $settings['template'] : '',
				'flow'     => isset( $settings['flow'] ) ? (string) $settings['flow'] : '',
			)
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template markup is escaped in the view.
	}
}
