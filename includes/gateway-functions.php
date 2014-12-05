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
	return $gateways->enabled_gateways;
}


/**
 * Send payment / subscription data to gateway
 *
 * @access      private
 * @return      array
*/
function rcp_send_to_gateway( $gateway, $subscription_data ) {
	do_action( 'rcp_gateway_' . $gateway, $subscription_data );
}