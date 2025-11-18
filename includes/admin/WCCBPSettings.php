<?php

/**
 * Admin settings in WooCommerce
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCCBPSettings {

	/**
	 * Plugin ID
	 *
	 * @since 1.0
	 * @var string
	 */
	protected $id = 'wccbp';

	/**
	 * Initialize settings hooks
	 *
	 * @since 1.0
	 * @return WCCBPSettings Current instance
	 */
	public function init(): WCCBPSettings {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'settings_page' ) );
		add_action( 'woocommerce_update_options_' . $this->id, array( $this, 'update_options' ) );
		return $this;
	}

	/**
	 * Add settings tab to WooCommerce settings
	 *
	 * @since 1.0
	 * @param array $settings_tabs Existing settings tabs.
	 * @return array Updated settings tabs array
	 */
	public function add_settings_settings_tab( array $settings_tabs ): array {

		$settings_tabs[ $this->id ] = __( 'WCCBP', 'wccbp' );

		return $settings_tabs;
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0
	 * @return void
	 */
	public function settings_page(): void {
		woocommerce_admin_fields( $this->create_tab_section() );
		wp_nonce_field( 'wccbp_subscription_settings', '_wccbpnonce', false );
	}


	/**
	 * Create input field for every available payment gateway
	 *
	 * @since 1.0
	 * @return array List of field configuration arrays
	 */
	public function create_fields(): array {
		$available_gateways = WC()->payment_gateways->payment_gateways();

		$fields = array();

		foreach ( $available_gateways as $gateway ) {
			$fields[] = array(
				'name' => $gateway->method_title ? $gateway->method_title : $gateway->id,
				'type' => 'multi_select_countries',
				'id'   => $this->id . '_' . $gateway->id,
			);
		}

		return $fields;
	}


	/**
	 * Create section and include input fields in section
	 *
	 * @since 1.0
	 * @return array Complete settings section configuration
	 */
	public function create_tab_section(): array {
		$section = array();

		$section[] = array(
			'name' => __( 'Country Based Payments', 'wccbp' ),
			'desc' => __( 'Select in which countries payment gateways will be available', 'wccbp' ),
			'type' => 'title',
			'id'   => $this->id . '_title',
		);

		$section = array_merge( $section, $this->create_fields() );

		$section[] = array(
			'type' => 'sectionend',
			'id'   => $this->id . '_title',
		);

		return $section;
	}


	/**
	 * Update setting fields with nonce verification
	 *
	 * @since 1.0
	 * @return void
	 */
	public function update_options(): void {
		// Verify nonce for security
		if ( empty( $_POST['_wccbpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wccbpnonce'] ) ), 'wccbp_subscription_settings' ) ) {
			// Log security event if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WCCBP: Nonce verification failed in settings update' );
			}
			return;
		}
		woocommerce_update_options( $this->create_fields() );

		// Clear cache after settings update
		$this->clear_gateway_cache();
	}

	/**
	 * Clear gateway availability cache
	 *
	 * @since 1.5.1
	 * @return void
	 */
	private function clear_gateway_cache(): void {
		// Clear WordPress object cache for this cache group
		wp_cache_flush_group( 'wccbp_gateway_availability' );

		// Log cache clear if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WCCBP: Gateway availability cache cleared after settings update' );
		}
	}
}
