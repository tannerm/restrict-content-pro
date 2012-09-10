<?php

// returns a list of all available gateways
function rcp_get_payment_gateways() {
	
	// default, built-in gateways
	$gateways = array(
		'paypal' => 'PayPal'
	);
	
	$gateways = apply_filters('rcp_payment_gateways', $gateways);
	
	return $gateways;
}

// returns a list of all enabled gateways
function rcp_get_enabled_payment_gateways() {
	global $rcp_options;
	$gateways = rcp_get_payment_gateways();
	$enabled_gateways = isset( $rcp_options['gateways'] ) ? $rcp_options['gateways'] : false;
	$gateway_list = array();
	if( $enabled_gateways ) {
		foreach($gateways as $key => $gateway) :
			if(isset($enabled_gateways[$key]) && $enabled_gateways[$key] == 1) :
				$gateway_list[$key] = $gateway;
			endif;
		endforeach;
	} else {
		$gateway_list['paypal'] = 'PayPal';
	}
	return $gateway_list;
}


// sends the registration data to the specified gateway
function rcp_send_to_gateway($gateway, $subscription_data) {
	do_action('rcp_gateway_' . $gateway, $subscription_data);
}