<?php

function rcp_setup_cron_jobs() {

	if ( ! wp_next_scheduled( 'rcp_expired_users_check' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_expired_users_check' );
	}

	if ( ! wp_next_scheduled( 'rcp_send_expiring_soon_notice' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'daily', 'rcp_send_expiring_soon_notice' );
	}
}
add_action('wp', 'rcp_setup_cron_jobs');

// runs each day and checks for expired members. Each member gets an email on the day of their expiration
function rcp_check_for_expired_users() {
	
	$args = array(
		'meta_query'     => array(
			'relation'   => 'AND',
			array(
				'key'    => 'rcp_expiration',
				'value'  => current_time( 'mysql' ),
				'type'   => 'DATETIME',
				'compare'=> '<'
			),
			array(
				'key'    => 'rcp_status',
				'value'  => 'active'
			),
			array(
				'key'    => 'rcp_recurring',
				'compare'=> 'NOT EXISTS'
			)
		),
		'number' 		=> 9999,
		'count_total' 	=> false,
		'fields'        => 'ids'
	);

	$expired_members     = get_users( $args );
	if( $expired_members ) {
		foreach( $expired_members as $member ) {

			$expiration_date = rcp_get_expiration_timestamp( $member );
			if( $expiration_date ) {
				$expiration_date += 86400; // to make sure we have given PayPal enough time to send the IPN

				if( rcp_is_expired( $member ) && current_time( 'timestamp' ) > $expiration_date ) {
					rcp_email_subscription_status( $member, 'expired' );
					rcp_set_status( $member, 'expired' );
					add_user_meta( $member, '_rcp_expired_email_sent', 'yes' );
				}
			}
		}
	}
}
add_action( 'rcp_expired_users_check', 'rcp_check_for_expired_users' );


// runs each day and checks for expired members. Each member gets an email on the day of their expiration
function rcp_check_for_soon_to_expire_users() {

	$renewal_period = rcp_get_renewal_reminder_period();

	if( 'none' == $renewal_period )
		return; // Don't send renewal reminders

	$args = array(
		'meta_query'     => array(
			'relation'   => 'AND',
			array(
				'key'    => 'rcp_expiration',
				'value'  => current_time( 'mysql' ),
				'type'   => 'DATETIME',
				'compare'=> '>='
			),
			array(
				'key'    => 'rcp_expiration',
				'value'  => date( 'Y-m-d H:i:s', strtotime( $renewal_period ) ),
				'type'   => 'DATETIME',
				'compare'=> '<='
			),
			array(
				'key'    => 'rcp_recurring',
				'compare'=> 'NOT EXISTS'
			)
		),
		'number' 		=> 9999,
		'count_total' 	=> false,
		'fields'        => 'ids'
	);

	$expiring_members = get_users( $args );
	if( $expiring_members ) {
		foreach( $expiring_members as $member ) {

			if( get_user_meta( $member, '_rcp_expiring_soon_email_sent', true ) )
				continue;

			rcp_email_expiring_notice( $member );
			add_user_meta( $member, '_rcp_expiring_soon_email_sent', 'yes' );

		}
	}
}
add_action( 'rcp_send_expiring_soon_notice', 'rcp_check_for_soon_to_expire_users' );
