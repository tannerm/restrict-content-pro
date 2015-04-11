<?php

/**
 * Determine if PayPal API access is enabled
 *
 * @access      public
 * @since       2.1
 */
function rcp_has_paypal_api_access() {
	global $rcp_options;

	$ret    = false;
	$prefix = 'live_';

	if( isset( $rcp_options['sandbox'] ) ) {
		$prefix = 'test_';
	}

	$username  = $prefix . 'paypal_api_username';
	$password  = $prefix . 'paypal_api_password';
	$signature = $prefix . 'paypal_api_signature';

	if( ! empty( $rcp_options[ $username ] ) && ! empty( $rcp_options[ $password ] ) && ! empty( $rcp_options[ $signature ] ) ) {

		$ret = true;

	}

	return $ret;
}