<?php

/**
 * Cancel an Authorize.net subscriber
 *
 * @access      private
 * @since       2.7
 */
function rcp_authnet_cancel_member( $member_id = 0 ) {

	global $rcp_options;

	$ret             = true;
	$api_login_id    = isset( $rcp_options['authorize_api_login'] )  ? sanitize_text_field( $rcp_options['authorize_api_login'] )  : '';
	$transaction_key = isset( $rcp_options['authorize_txn_key'] )    ? sanitize_text_field( $rcp_options['authorize_txn_key'] )    : '';
	$md5_hash_value  = isset( $rcp_options['authorize_hash_value'] ) ? sanitize_text_field( $rcp_options['authorize_hash_value'] ) : '';

	require_once RCP_PLUGIN_DIR . 'includes/libraries/anet_php_sdk/AuthorizeNet.php';

	$member     = new RCP_Member( $member_id );
	$profile_id = str_replace( 'anet_', '', $member->get_payment_profile_id() );

	$arb        = new AuthorizeNetARB( $api_login_id, $transaction_key );
	$arb->setSandbox( rcp_is_sandbox() );

	$response   = $arb->cancelSubscription( $profile_id );

	if( ! $response->isOK() || $response->isError() ) {

		$error = $response->getErrorMessage();
		$ret   = new WP_Error( 'rcp_authnet_error', $error );

	}

	return $ret;
}


/**
 * Determine if a member is an Authorize.net Customer
 *
 * @since       2.7
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
*/
function rcp_is_authnet_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$member = new RCP_Member( $user_id );

	$profile_id = $member->get_payment_profile_id();

	// Check if the member is an Authorize.net customer
	if( false !== strpos( $profile_id, 'anet_' ) ) {

		$ret = true;

	}

	return (bool) apply_filters( 'rcp_is_authorizenet_subscriber', $ret, $user_id );
}