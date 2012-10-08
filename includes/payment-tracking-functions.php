<?php

/***************************************************
* functions used for tracking payments and earnings
***************************************************/

// retrieve payments from the database
function rcp_get_payments( $offset = 0, $number = 20 ) {
	global $wpdb, $rcp_payments_db_name;
	if( $number > 0 ) {
		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_payments_db_name . " ORDER BY id DESC LIMIT " . $offset . "," . $number . ";" ) );
	} else {
		// when retrieving all payments, the query is cached
		$payments = get_transient( 'rcp_payments' );
		if( $payments === false ) {
			$payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $rcp_payments_db_name . " ORDER BY id DESC;" ) ); // this is to get all payments
			set_transient( 'rcp_payments', $payments, 10800 );
		}
	}
	return $payments;
}


// returns the total number of payments recorded
function rcp_count_payments() {
	global $wpdb, $rcp_payments_db_name;
	$count = get_transient( 'rcp_payments_count' );
	if( $count === false ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . $rcp_payments_db_name . ";" ) );
		set_transient( 'rcp_payments_count', $count, 10800 );
	}
	return $count;
}

function rcp_get_earnings() {
	global $wpdb, $rcp_payments_db_name;
	$payments = get_transient( 'rcp_earnings' );
	if( $payments === false ) {
		$payments = $wpdb->get_results( $wpdb->prepare( "SELECT amount FROM " . $rcp_payments_db_name . ";" ) );
		// cache the payments query
		set_transient( 'rcp_earnings', $payments, 10800 );
	}
	$total = 0;
	if( $payments ) :
		foreach( $payments as $payment ) :
			$total = $total + $payment->amount;
		endforeach;
	endif;
	return $total;
}

function rcp_insert_payment( $payment_data = array() ) {
	global $wpdb, $rcp_payments_db_name;
		
	$amount = $payment_data['amount'];
	if( $payment_data['amount'] == '' )
		$amount = $payment_data['amount2'];
	
	if( rcp_check_for_existing_payment( $payment_data['payment_type'], $payment_data['date'], $payment_data['subscription_key'] ) )
		return;

	$wpdb->insert( 
		$rcp_payments_db_name, 
		array( 
			'subscription' 		=> $payment_data['subscription'], 
			'date' 				=> $payment_data['date'], 
			'amount' 			=> $amount,
			'user_id' 			=> $payment_data['user_id'],
			'payment_type' 		=> $payment_data['payment_type'],
			'subscription_key' 	=> $payment_data['subscription_key']
		), 
		array( 
			'%s', 
			'%s',
			'%s',
			'%d',
			'%s',
			'%s'
		) 
	);
	
	// if insert was succesful, return the payment ID
	if( $wpdb->insert_id ) {
		// clear the payment caches
		delete_transient( 'rcp_payments' );
		delete_transient( 'rcp_earnings' );
		delete_transient( 'rcp_payments_count' );
		do_action( 'rcp_insert_payment', $wpdb->insert_id, $payment_data, $amount );
		return $wpdb->insert_id;
	}
	// return false if payment wasn't recorded
	return false;
}

function rcp_check_for_existing_payment( $type, $date, $subscription_key ) {
	
	global $wpdb, $rcp_payments_db_name;
	
	if( $type == 'subscr_payment' ) {
		// recurring payment
		if( $wpdb->get_results( $wpdb->prepare( "SELECT id FROM " . $rcp_payments_db_name . " WHERE `date`='" . $date . "' AND `subscription_key`='" . $subscription_key . "';" ) ) )
			return true; // this payment already exists
	} elseif( $type == 'web_accept' ) {
		// one time payment
		if( $wpdb->get_results( $wpdb->prepare("SELECT id FROM " . $rcp_payments_db_name . " WHERE `subscription_key`='" . $subscription_key . "';" ) ) )
			return true; // this payment already exists
	}
	return false; // this payment doesn't exist
}