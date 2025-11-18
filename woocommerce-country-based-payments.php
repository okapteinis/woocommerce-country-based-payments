<?php
/**
 * Plugin Name: Country Based Payments for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/woocommerce-country-based-payments/
 * Description: Choose in which country certain payment gateway will be available
 * Version:     1.5
 * Author:      Ivan Paulin
 * Author URI:  http://ivanpaulin.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: wccbp
 * WC requires at least: 5.0
 * WC tested up to: 8.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the plugin, settings page and handle plugin logic
 *
 * @since 1.0
 */
class WoocommerceCountryBasedPayment {

	/**
	 * Plugin ID
	 *
	 * @since 1.0
	 * @var string
	 */
	private $id;

	/**
	 * Cached gateway availability settings
	 *
	 * @since 1.5.1
	 * @var array
	 */
	private $gateway_cache = array();

	/**
	 * Construct plugin
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->id = 'wccbp';

		if ( is_admin() && ! wp_doing_ajax() ) {
		$this->load_settings();
	}

		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Check if ajax request.
		if ( ! is_admin() ) {
			// Fix WPML WooCommerce Multilingual error.
			add_filter( 'wcml_supported_currency_payment_gateways', array( $this, 'available_payment_gateways' ), 90, 1 );

			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), 10, 1 );
		}

		// Check if pay_for page.
		if ( ! is_admin() && isset( $_GET['pay_for_order'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['pay_for_order'] ) ) ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways_after_cancelation' ), 10, 1 );
		}

	// Declare HPOS compatibility
	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );
	}

	/**
	 * Load textdomain
	 *
	 * @since 1.0
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain( 'wccbp', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Load admin settings
	 *
	 * @since 1.0
	 * @return WCCBPSettings Settings instance
	 */
	public function load_settings(): WCCBPSettings {
		require 'includes/admin/WCCBPSettings.php';
		return ( new WCCBPSettings() )->init();
	}


	/**
	 * List through available payment gateways,
	 * check if certain payment gateway is enabled for country,
	 * if no, unset it from $payment_gateways array
	 *
	 * @since 1.0
	 * @param array $payment_gateways List of available gateways in system.
	 * @return array Updated list of available payment gateways
	 */
	public function available_payment_gateways( array $payment_gateways ): array {
		if ( is_null( WC()->customer ) || ! WC()->customer instanceof WC_Customer ) {
			return $payment_gateways;
		}

		$customer_country = WC()->customer->get_billing_country();

		foreach ( $payment_gateways as $key => $value ) {
			// Check if WCML array.
			$gateway_id = ( is_object( $value ) && isset( $value->id ) ) ? $value->id : $key;
			$gateway_availability = $this->get_gateway_availability( $gateway_id );

			if ( is_array( $gateway_availability ) && ! in_array( $customer_country, $gateway_availability, true ) ) {
				unset( $payment_gateways[ $gateway_id ] );
			}
		}
		return $payment_gateways;
	}

	/**
	 * List through available payment gateways,
	 * if customer gets redirected to the pay_for page
	 * after a payment cancellation
	 *
	 * @since 1.0
	 * @param array $payment_gateways List of available gateways in system.
	 * @return array Updated list of available payment gateways
	 */
	public function available_payment_gateways_after_cancelation( array $payment_gateways ): array {
		// Sanitize and validate the order key
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( empty( $order_key ) ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WCCBP: Missing order key in payment retry request' );
			}
			return $payment_gateways;
		}

		// Get order ID from order key
		$order_id = wc_get_order_id_by_order_key( $order_key );

		if ( ! $order_id ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WCCBP: Invalid order key provided: ' . $order_key );
			}
			return $payment_gateways;
		}

		// Get order object using modern WooCommerce function
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WCCBP: Unable to retrieve order with ID: ' . $order_id );
			}
			return $payment_gateways;
		}

		// Get billing country using modern WooCommerce method
		$selected_country = $order->get_billing_country();

		if ( empty( $selected_country ) ) {
			// Log error if WP_DEBUG is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WCCBP: No billing country found for order ID: ' . $order_id );
			}
			return $payment_gateways;
		}

		foreach ( $payment_gateways as $gateway ) {
			$gateway_availability = $this->get_gateway_availability( $gateway->id );

			if ( is_array( $gateway_availability ) && ! in_array( $selected_country, $gateway_availability, true ) ) {
				unset( $payment_gateways[ $gateway->id ] );
			}
		}

		return $payment_gateways;
	}

	/**
	 * Get gateway availability settings with caching
	 *
	 * @since 1.5.1
	 * @param string $gateway_id Gateway identifier.
	 * @return array|false Array of country codes or false if unrestricted
	 */
	private function get_gateway_availability( string $gateway_id ) {
		$cache_key = $this->id . '_' . $gateway_id;

		// Check if already cached in class property
		if ( isset( $this->gateway_cache[ $cache_key ] ) ) {
			return $this->gateway_cache[ $cache_key ];
		}

		// Try to get from WordPress object cache
		$cached_value = wp_cache_get( $cache_key, 'wccbp_gateway_availability' );

		if ( false !== $cached_value ) {
			// Store in class property for subsequent calls in same request
			$this->gateway_cache[ $cache_key ] = $cached_value;
			return $cached_value;
		}

		// Get from database if not cached
		$gateway_availability = get_option( $cache_key, false );

		// Cache for future requests (1 hour)
		wp_cache_set( $cache_key, $gateway_availability, 'wccbp_gateway_availability', HOUR_IN_SECONDS );

		// Store in class property for subsequent calls in same request
		$this->gateway_cache[ $cache_key ] = $gateway_availability;

		return $gateway_availability;
	}
}

/**
 * Check if WooCommerce is active
 */
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	// Plugin is activated.
	new WoocommerceCountryBasedPayment();
}

// Load Freemius SDK
// Create a helper function for easy SDK access.
function wcbp_fs() {
	global $wcbp_fs;

	if ( ! isset( $wcbp_fs ) ) {
			// Include Freemius SDK.
			require_once dirname(__FILE__) . '/includes/freemius/start.php';

			$wcbp_fs = fs_dynamic_init( array(
					'id'                  => '2788',
					'slug'                => 'woocommerce-country-based-payments',
					'type'                => 'plugin',
					'public_key'          => 'pk_cbdb518bd47595e667e3992ea2e2f',
					'is_premium'          => false,
					'has_addons'          => false,
					'has_paid_plans'      => false,
					'menu'                => array(
							'slug'           => 'wc-settings',
							'override_exact' => true,
							'account'        => false,
							'contact'        => false,
							'support'        => false,
							'parent'         => array(
									'slug' => 'woocommerce',
							),
					),
			) );
	}

	return $wcbp_fs;
}

// Init Freemius.
wcbp_fs();
// Signal that SDK was initiated.
do_action( 'wcbp_fs_loaded' );

/**
 * Get WCCBP settings URL for Freemius integration
 *
 * @since 1.2.0
 * @return string Settings page URL
 */
function wcbp_fs_settings_url(): string {
	return admin_url( 'admin.php?page=wc-settings&tab=wccbp' );
}

wcbp_fs()->add_filter( 'connect_url', 'wcbp_fs_settings_url' );
wcbp_fs()->add_filter( 'after_skip_url', 'wcbp_fs_settings_url' );
wcbp_fs()->add_filter( 'after_connect_url', 'wcbp_fs_settings_url' );
wcbp_fs()->add_filter( 'after_pending_connect_url', 'wcbp_fs_settings_url' );
