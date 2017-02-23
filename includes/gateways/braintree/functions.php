<?php
/**
 * Braintree Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/Braintree/Functions
 * @copyright   Copyright (c) 2017, Sandhills Development
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
 */

/**
 * Determines if a member is a Braintree customer.
 *
 * @since  2.8
 * @param  int  $member_id The ID of the user to check
 * @return bool True if the member is a Braintree customer, false if not.
*/
function rcp_is_braintree_subscriber( $member_id = 0 ) {

	if ( empty( $member_id ) ) {
		$member_id = get_current_user_id();
	}

	$ret = false;

	$member = new RCP_Member( $member_id );

	$profile_id = $member->get_payment_profile_id();

	if ( false !== strpos( $profile_id, 'bt_' ) ) {
		$ret = true;
	}

	return (bool) apply_filters( 'rcp_is_braintree_subscriber', $ret, $member_id );
}

/**
 * Determines if all necessary Braintree API credentials are available.
 *
 * @since  2.7
 * @return bool
 */
function rcp_has_braintree_api_access() {

	global $rcp_options;

	if ( rcp_is_sandbox() ) {
		$merchant_id    = ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_merchantId'] ) : '';
		$public_key     = ! empty( $rcp_options['braintree_sandbox_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_publicKey'] ) : '';
		$private_key    = ! empty( $rcp_options['braintree_sandbox_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_privateKey'] ) : '';
		$encryption_key = ! empty( $rcp_options['braintree_sandbox_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_encryptionKey'] ) : '';

	} else {
		$merchant_id    = ! empty( $rcp_options['braintree_live_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_live_merchantId'] ) : '';
		$public_key     = ! empty( $rcp_options['braintree_live_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_publicKey'] ) : '';
		$private_key    = ! empty( $rcp_options['braintree_live_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_privateKey'] ) : '';
		$encryption_key = ! empty( $rcp_options['braintree_live_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_encryptionKey'] ) : '';
	}

	if ( ! empty( $merchant_id ) && ! empty( $public_key ) && ! empty( $private_key ) && ! empty( $encryption_key ) ) {
		return true;
	}

	return false;
}
/**
 * Cancels a Braintree subscriber.
 *
 * @since 2.8
 * @param int $member_id The member ID to cancel.
 * @return bool|WP_Error
 */
function rcp_braintree_cancel_member( $member_id = 0 ) {

	global $rcp_options;

	$ret = true;

	if ( rcp_is_sandbox() ) {
		$merchant_id    = ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_merchantId'] ) : '';
		$public_key     = ! empty( $rcp_options['braintree_sandbox_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_publicKey'] ) : '';
		$private_key    = ! empty( $rcp_options['braintree_sandbox_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_privateKey'] ) : '';
		$encryption_key = ! empty( $rcp_options['braintree_sandbox_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_encryptionKey'] ) : '';
		$environment    = 'sandbox';

	} else {
		$merchant_id    = ! empty( $rcp_options['braintree_live_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_live_merchantId'] ) : '';
		$public_key     = ! empty( $rcp_options['braintree_live_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_publicKey'] ) : '';
		$private_key    = ! empty( $rcp_options['braintree_live_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_privateKey'] ) : '';
		$encryption_key = ! empty( $rcp_options['braintree_live_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_encryptionKey'] ) : '';
		$environment    = 'production';
	}

	require_once RCP_PLUGIN_DIR . 'includes/libraries/braintree/lib/Braintree.php';

	Braintree_Configuration::environment( $environment );
	Braintree_Configuration::merchantId( $merchant_id );
	Braintree_Configuration::publicKey( $public_key );
	Braintree_Configuration::privateKey( $private_key );

	$member = new RCP_Member( $member_id );

	try {
		$result = Braintree_Subscription::cancel( $member->get_merchant_subscription_id() );

		if ( ! $result->success ) {
			$ret = new WP_Error( 'rcp_braintree_error', $result->message );
		}

	} catch ( Exception $e ) {
		$ret = new WP_Error( 'rcp_braintree_error', $e->getMessage() );
	}

	return $ret;
}