<?php

function rcp_process_paypal($subscription_data) {

	global $rcp_options;

	$paypal_redirect = '';
	$paypal_email = $rcp_options['paypal_email'];
	$listener_url = home_url('/') . '?listener=IPN';
	
	if( isset($rcp_options['sandbox'])) {
		$paypal_redirect = 'https://www.sandbox.paypal.com/cgi-bin/webscr/?';
	} else {
		$paypal_redirect = 'https://www.paypal.com/cgi-bin/webscr/?';
	}	
	
	// recurring paypal payment
	if($subscription_data['auto_renew']) {
		// recurring paypal payment
		$paypal_redirect .= 'cmd=_xclick-subscriptions&src=1&sra=1';
		$paypal_redirect .= '&a3=' . $subscription_data['price'];
		$paypal_redirect .= '&p3=' . $subscription_data['length'];
		switch ($subscription_data['length_unit']) :
			case "day" :
				$paypal_redirect .= '&t3=D';
			break;
			case "month" :
				$paypal_redirect .= '&t3=M';
			break;
			case "year" :
				$paypal_redirect .= '&t3=Y';
			break;
		endswitch;
	} else {
		// one time payment
		$paypal_redirect .= 'cmd=_xclick&amount=' . $subscription_data['price'];
	}
	
	$paypal_redirect .= '&business=' . $paypal_email;
	$paypal_redirect .= '&item_name=' . $subscription_data['subscription_name'];
	$paypal_redirect .= '&email=' . $subscription_data['user_email'];
	$paypal_redirect .= '&no_shipping=1&no_note=1&item_number=' . $subscription_data['key'];
	$paypal_redirect .= '&currency_code=' . $subscription_data['currency'];
	$paypal_redirect .= '&charset=UTF-8&return=' . urlencode($subscription_data['return_url']);
	$paypal_redirect .= '&notify_url=' . urlencode($listener_url);
	$paypal_redirect .= '&rm=2&custom=' . $subscription_data['user_id'];
		
	// Redirect to paypal
	header('Location: ' . $paypal_redirect);
	exit;
	
}
add_action('rcp_gateway_paypal', 'rcp_process_paypal');

ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/ipn_errors.log');

function rcp_check_ipn() {

	global $rcp_options;

	// instantiate the IpnListener class
	include('paypal/ipnlistener.php');
	$listener = new IpnListener();

	if(isset($rcp_options['sandbox']) && $rcp_options['sandbox'])
		$listener->use_sandbox = true;

	//$listener->use_ssl = false;
	
	//To post using the fsockopen() function rather than cURL, use:
	if(isset($rcp_options['disable_curl']))
		$listener->use_curl = false;

	try {
		$listener->requirePostMethod();
		$verified = $listener->processIpn();
	} catch (Exception $e) {
		if(isset($rcp_options['log_ipn_errors']) && $rcp_options['log_ipn_errors']) {
			error_log($e->getMessage());
		}
		exit(0);
	}


	/*
	The processIpn() method returned true if the IPN was "VERIFIED" and false if it
	was "INVALID".
	*/
	if ($verified) {
		$user_id 			= $_POST['custom'];
		$subscription_key 	= $_POST['item_number'];
		$amount 			= $_POST['mc_gross'];
		$amount2 			= $_POST['mc_amount3'];
		$payment_status 	= $_POST['payment_status'];
		$currency_code		= $_POST['mc_currency'];
		$subscription_price = rcp_get_subscription_price(rcp_get_subscription_id($user_id));
		
		// setup the payment info in an array for storage
		$payment_data = array(
			'date' => date('Y-m-d g:i:s', strtotime($_POST['payment_date'])),
			'subscription' => $_POST['item_name'],
			'payment_type' => $_POST['txn_type'],
			'payer_email' => $_POST['payer_email'],
			'subscription_key' => $subscription_key,
			'amount' => $amount,
			'amount2' => $amount2,
			'user_id' => $user_id
		);
		
		
		if($_POST['txn_type'] == 'web_accept' || $_POST['txn_type'] == 'subscr_payment') {
			// only check for an existing payment if this is a payment IPD request
			if(rcp_check_for_existing_payment($_POST['txn_type'], $_POST['payment_date'], $subscription_key))
				return; // this IPN request has already been processed
		}
		if(isset($rcp_options['email_ipn_reports']) && $rcp_options['email_ipn_reports']) {
			wp_mail(get_bloginfo('admin_email'), __('IPN report', 'rcp'), $listener->getTextReport());
		}
	
		/* do some quick checks to make sure all necessary data validates */
		
		if($subscription_price != $amount && $subscription_price != $amount2) {
			// the subscription price doesn't match, so lets check to see if it matches with a discount code
			if(!rcp_check_paypal_return_price_after_discount($subscription_price, $amount, $amount2, $user_id)) {
				return;
			}
		}
		if(rcp_get_subscription_key($user_id) !== $subscription_key) {
			// the subscription key is invalid
			return;
		}
		if($currency_code != $rcp_options['currency']) {
			// the currency code is invalid
			return;
		}
		
		/* now process the kind of subscription/payment */
		
		// Subscriptions
		switch ($_POST['txn_type']) :
			
			case "subscr_signup" :
				// when a new user signs up
				
				// set the user's status to active
				rcp_set_status($user_id, 'active');
				
				wp_new_user_notification($user_id);
				// send welcome email
				rcp_email_subscription_status($user_id, 'active');

			break;
			case "subscr_payment" :
				// when a user makes a recurring payment
				// record this payment in the database
				rcp_insert_payment($payment_data);
				$subscription = rcp_get_subscription_details_by_name($payment_data['subscription']);
				
				// update the user's expiration to correspond with the new payment
				$member_new_expiration = date('Y-m-d', strtotime('+' . $subscription->duration . ' ' . $subscription->duration_unit));
				update_user_meta( $user_id, 'rcp_expiration', $member_new_expiration );
				// make sure the user's status is active
				rcp_set_status($user_id, 'active');
				
			break;
			case "subscr_cancel" :
				// user is not canceled until end of term
				
				// set the use to no longer be recurring
				delete_user_meta( $user_id, 'rcp_recurring');
				
				// send sub cancelled email
				rcp_email_subscription_status($user_id, 'cancelled');
			break;
			case "subscr_failed" :
			case "subscr_eot" :
				// user's subscription has reach the end of its term
				
				// set the use to no longer be recurring
				update_user_meta( $user_id, 'rcp_recurring', 'no');
				rcp_set_status($user_id, 'expired');
				// send expired email
				rcp_email_subscription_status($user_id, 'expired');
			break;
			default;
			break;
		endswitch;
		
		// Single Payments
		switch ($_POST['txn_type']) :
			
			case "cart" :
			case "express_checkout" :
			case "web_accept" :
				
				switch (strtolower($payment_status)) :
		            case 'completed' :
						// set this user to active
						rcp_set_status($user_id, 'active');
						
						rcp_insert_payment($payment_data);
						
						rcp_email_subscription_status($user_id, 'active');
						// send welcome email here
						wp_new_user_notification($user_id);
		            break;
		            case 'denied' :
		            case 'expired' :
		            case 'failed' :
		            case 'voided' :
						rcp_set_status($user_id, 'cancelled');
						// send cancelled email here
		            break;
		        endswitch;
				
			break;
			default :
			break;
			
		endswitch;

	} else {
		if(isset($rcp_options['email_ipn_reports']) && $rcp_options['email_ipn_reports']) {
			// an invalid IPN attempt was made. Send an email to the admin account to investigate
			wp_mail(get_bloginfo('admin_email'), __('Invalid IPN', 'rcp'), $listener->getTextReport());
		}
	}
}
add_action('verify-paypal-ipn', 'rcp_check_ipn');

function rcp_listen_for_paypal_ipn() {
	if(isset($_GET['listener']) && $_GET['listener'] == 'IPN') {
		do_action('verify-paypal-ipn');
	}
}
add_action('init', 'rcp_listen_for_paypal_ipn');