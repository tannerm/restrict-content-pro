<?php
/**
 * Gateway Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Load additional gateway include files
 *
 * @uses rcp_get_payment_gateways()
 *
 * @access private
 * @since  2.1
 * @return void
*/
function rcp_load_gateway_files() {
	foreach( rcp_get_payment_gateways() as $key => $gateway ) {
		if( file_exists( RCP_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php';
		}
	}
}
add_action( 'plugins_loaded', 'rcp_load_gateway_files', 9999 );

/**
 * Get all available payment gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_payment_gateways() {
	$gateways = new RCP_Payment_Gateways;
	return $gateways->available_gateways;
}

/**
 * Return list of active gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_enabled_payment_gateways() {

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) ) {

			$gateways->enabled_gateways[ $key ] = $gateway['label'];

		}

	}

	return $gateways->enabled_gateways;
}

/**
 * Determine if a gateway is enabled
 *
 * @param string $id ID of the gateway to check.
 *
 * @access public
 * @return bool
 */
function rcp_is_gateway_enabled( $id = '' ) {
	$gateways = new RCP_Payment_Gateways;
	return $gateways->is_gateway_enabled( $id );
}

/**
 * Send payment / subscription data to gateway
 *
 * @param string $gateway           ID of the gateway.
 * @param array  $subscription_data Subscription data.
 *
 * @access      private
 * @return      void
 */
function rcp_send_to_gateway( $gateway, $subscription_data ) {

	if( has_action( 'rcp_gateway_' . $gateway ) ) {

		do_action( 'rcp_gateway_' . $gateway, $subscription_data );

	} else {

		$gateways = new RCP_Payment_Gateways;
		$gateway  = $gateways->get_gateway( $gateway );
		$gateway  = new $gateway['class']( $subscription_data );

		$gateway->process_signup();

	}

}

/**
 * Determines if a gateway supports recurring payments
 *
 * @param string $gateway ID of the gateway to check.
 * @param string $item    Feature to check support for.
 *
 * @access  public
 * @since   2.1
 * @return  bool
 */
function rcp_gateway_supports( $gateway = 'paypal', $item = 'recurring' ) {

	$ret      = true;
	$gateways = new RCP_Payment_Gateways;
	$gateway  = $gateways->get_gateway( $gateway );

	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$ret     = $gateway->supports( sanitize_text_field( $item ) );

	}

	return $ret;

}

/**
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_process_gateway_webooks() {

	if ( ! apply_filters( 'rcp_process_gateway_webhooks', ! empty( $_GET['listener'] ) ) ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->available_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

			$gateway = new $gateway['class'];
			$gateway->process_webhooks();

		}

	}

}
add_action( 'init', 'rcp_process_gateway_webooks', -99999 );

/**
 * Process gateway confirmaions
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_process_gateway_confirmations() {

	global $rcp_options;

	if( empty( $rcp_options['registration_page'] ) ) {
		return;
	}

	if( empty( $_GET['rcp-confirm'] ) ) {
		return;
	}

	if( ! rcp_is_registration_page() ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;
	$gateway  = sanitize_text_field( $_GET['rcp-confirm'] );

	if( ! $gateways->is_gateway_enabled( $gateway ) ) {
		return;
	}

	$gateway = $gateways->get_gateway( $gateway );

	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$gateway->process_confirmation();

	}

}
add_action( 'template_redirect', 'rcp_process_gateway_confirmations', -99999 );

/**
 * Load gateway scripts on registration page
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_load_gateway_scripts() {

	global $rcp_options;

	$load_scripts = rcp_is_registration_page() || defined( 'RCP_LOAD_SCRIPTS_GLOBALLY' );
	$gateways     = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		// Stripe.js is loaded on all pages for advanced fraud functionality. Other scripts are only loaded on the registration page.
		if( is_array( $gateway ) && isset( $gateway['class'] ) && ( $load_scripts || in_array( $key, array( 'stripe', 'stripe_checkout' ) ) ) ) {

			$gateway = new $gateway['class'];
			$gateway->scripts();

		}

	}

}
add_action( 'wp_enqueue_scripts', 'rcp_load_gateway_scripts', 100 );

/**
 * Process an update card form request
 *
 * @uses rcp_member_can_update_billing_card()
 *
 * @access      private
 * @since       2.1
 * @return      void
 */
function rcp_process_update_card_form_post() {

	if( ! is_user_logged_in() ) {
		return;
	}

	if( is_admin() ) {
		return;
	}

	if ( ! isset( $_POST['rcp_update_card_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_update_card_nonce'], 'rcp-update-card-nonce' ) ) {
		return;
	}

	if( ! rcp_member_can_update_billing_card() ) {
		wp_die( __( 'Your account does not support updating your billing card', 'rcp' ), __( 'Error', 'rcp' ), array( 'response' => 403 ) );
	}

	$member = new RCP_Member( get_current_user_id() );

	if( $member ) {

		do_action( 'rcp_update_billing_card', $member->ID, $member );

	}

}
add_action( 'init', 'rcp_process_update_card_form_post' );

/**
 * Retrieve the full HTML link for the transaction ID on the merchant site
 *
 * @param object  $payment Payment object
 *
 * @access public
 * @since  2.6
 * @return string HTML link, or just the transaction ID.
 */
function rcp_get_merchant_transaction_id_link( $payment ) {

	global $rcp_options;

	$url  = '';
	$link = $payment->transaction_id;
	$test = rcp_is_sandbox();

	if( ! empty( $payment->transaction_id ) ) {

		$type = strtolower( $payment->payment_type );

		switch( $type ) {

			case 'web_accept' :
			case 'paypal express one time' :
			case 'recurring_payment' :
			case 'subscr_payment' :
			case 'recurring_payment_profile_created' :

				// PayPal

				$mode = $test ? 'sandbox.' : '';
				$url  = 'https://www.' . $mode . 'paypal.com/webscr?cmd=_history-details-from-hub&id=' . $payment->transaction_id;

				break;

			case 'credit card' :
			case 'credit card one time' :

				if( false !== strpos( $payment->transaction_id, 'ch_' ) ) {

					// Stripe

					$mode = $test ? 'test/' : '';
					$url  = 'https://dashboard.stripe.com/' . $mode . 'payments/' . $payment->transaction_id;

				} else if( is_numeric( $payment->transaction_id ) ) {

					// 2Checkout

					$mode = $test ? 'sandbox.' : '';
					$url  = 'https://' . $mode . '2checkout.com/sandbox/sales/detail?sale_id=' . $payment->transaction_id;

				}

				break;

			case 'braintree credit card one time' :
			case 'braintree credit card initial payment' :
			case 'braintree credit card' :

				$mode        = $test ? 'sandbox.' : '';
				$merchant_id = $test ? $rcp_options['braintree_sandbox_merchantId'] : $rcp_options['braintree_live_merchantId'];

				$url         = 'https://' . $mode . 'braintreegateway.com/merchants/' . $merchant_id . '/transactions/' . $payment->transaction_id;

				break;
		}

		if( ! empty( $url ) ) {

			$link = '<a href="' . esc_url( $url ) . '" class="rcp-payment-txn-id-link" target="_blank">' . $payment->transaction_id . '</a>';

		}

	}

	return apply_filters( 'rcp_merchant_transaction_id_link', $link, $payment );

}