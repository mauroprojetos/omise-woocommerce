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
		public function charge( $order_id, $order ) {
			$total      = $order->get_total();
			$currency   = $order->get_order_currency();
			$return_uri = add_query_arg(
				array( 'wc-api' => 'omise_konbini_callback', 'order_id' => $order_id ), home_url()
			);
			$metadata   = array_merge(
				apply_filters( 'omise_charge_params_metadata', array(), $order ),
				array( 'order_id' => $order_id ) // override order_id as a reference for webhook handlers.
			);

			return OmiseCharge::create( array(
				'amount'      => Omise_Money::to_subunit( $total, $currency ),
				'currency'    => $currency,
				'description' => apply_filters( 'omise_charge_params_description', 'WooCommerce Order id ' . $order_id, $order ),
				'source'      => array( 'type' => 'econtext', 'name' => 'ヤマダタロウ', 'email' => 'user@omise.co', 'phone_number' => '01234567891', ),
				'return_uri'  => $return_uri,
				'metadata'    => $metadata
			) );
		}

		/**
		 * @inheritdoc
		 */
		public function result( $order_id, $order, $charge ) {
			if ( self::STATUS_FAILED === $charge['status'] ) {
				return $this->payment_failed( $charge['failure_message'] . ' (code: ' . $charge['failure_code'] . ')' );
			}

			if ( self::STATUS_PENDING === $charge['status'] ) {
				$order->update_status( 'on-hold', __( 'Omise: Awaiting Konbini Payment to be paid.', 'omise' ) );

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}

			return $this->payment_failed(
				sprintf(
					__( 'Please feel free to try submitting your order again, or contact our support team if you have any questions (Your temporary order id is \'%s\')', 'omise' ),
					$order_id
				)
			);
		}
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
