<?php

/**
 * Register default payment gateways
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
 * Send payment / subscription data to gateway
 *
 * @access      private
 * @return      array
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
 * @access      public
 * @since      2.1
 * @return      bool
*/
function rcp_gateway_supports( $gateway = 'paypal', $item = 'recurring' ) {

	$ret      = true;
	$gateways = new RCP_Payment_Gateways;
	$gateway  = $gateways->get_gateway( $gateway );
	
	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$ret     = $gateway->supports( 'recurring' );

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
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_load_gateway_scripts() {

	global $rcp_options;

	if( ! is_page( $rcp_options['registration_page'] ) && ! rcp_is_registration_page() ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

			$gateway = new $gateway['class'];
			$gateway->scripts();

		}

	}

}
add_action( 'wp_enqueue_scripts', 'rcp_load_gateway_scripts', 100 );