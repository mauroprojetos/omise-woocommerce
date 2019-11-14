<?php
defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

function register_omise_konbini() {
	require_once dirname( __FILE__ ) . '/class-omise-payment.php';

	/**
	 * @since 3.10
	 */
	class Omise_Payment_Konbini extends Omise_Payment {
		public function __construct() {
			parent::__construct();

			$this->id                 = 'omise_konbini';
			$this->has_fields         = false;
			$this->method_title       = __( 'Convenience Store / Pay-easy / Online Banking', 'omise' );
			$this->method_description = wp_kses(
				__( 'Accept payments through <strong>Convenience Store</strong> / <strong>Pay-easy</strong> / <strong>Online Banking</strong> via Omise payment gateway.', 'omise' ),
				array( 'strong' => array() )
			);

			$this->init_form_fields();
			$this->init_settings();

			$this->title                = $this->get_option( 'title' );
			$this->description          = $this->get_option( 'description' );
			$this->restricted_countries = array( 'JP' );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
 		 * @see WC_Settings_API::init_form_fields()
 		 * @see woocommerce/includes/abstracts/abstract-wc-settings-api.php
 		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'omise' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Omise Convenience Store / Pay-easy / Online Banking Payment', 'omise' ),
					'default' => 'no'
				),

				'title' => array(
					'title'       => __( 'Title', 'omise' ),
					'type'        => 'text',
					'description' => __( 'This controls the title the user sees during checkout.', 'omise' ),
					'default'     => __( 'Convenience Store / Pay-easy / Online Banking', 'omise' ),
				),

				'description' => array(
					'title'       => __( 'Description', 'omise' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description the user sees during checkout.', 'omise' )
				),
			);
		}

		/**
 		 * @inheritdoc
 		 */
		public function charge( $order_id, $order ) { }

		/**
		 * @inheritdoc
		 */
		public function result( $order_id, $order, $charge ) { }
	}

	if ( ! function_exists( 'add_omise_konbini' ) ) {
		/**
		 * @param  array $methods
		 *
		 * @return array
		 */
		function add_omise_konbini( $methods ) {
			$methods[] = 'Omise_Payment_Konbini';
			return $methods;
		}

		add_filter( 'woocommerce_payment_gateways', 'add_omise_konbini' );
	}
}
