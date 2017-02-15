<?php
/**
 * Manual Payment Gateway
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/Manual
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_Manual extends RCP_Payment_Gateway {

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'fees';

	}

	/**
	 * Process registration
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_signup() {

		global $rcp_options;

		$member = new RCP_Member( $this->user_id );

		$old_level = get_user_meta( $member->ID, '_rcp_old_subscription_id', true );
		if ( ! empty( $old_level ) && $old_level == $this->subscription_id ) {
			$expiration = $member->calculate_expiration();
		} else {
			delete_user_meta( $member->ID, 'rcp_pending_expiration_date' );
			$expiration = $member->calculate_expiration( true );
		}

		$member->renew( false, 'pending', $expiration );

		// setup the payment info in an array for storage
		$payment_data = array(
			'subscription'     => $this->subscription_name,
			'payment_type'     => 'manual',
			'subscription_key' => $this->subscription_key,
			'amount'           => $this->amount + $this->signup_fee,
			'user_id'          => $this->user_id,
			'transaction_id'   => $this->generate_transaction_id()
		);

		$rcp_payments = new RCP_Payments();
		$payment_id   = $rcp_payments->insert( $payment_data );

		// Email site admin about the payment.
		if ( ! isset( $rcp_options['disable_new_user_notices'] ) ) {
			$admin_emails   = array();
			$admin_emails[] = get_option( 'admin_email' );
			$admin_emails   = apply_filters( 'rcp_admin_notice_emails', $admin_emails );

			$emails             = new RCP_Emails;
			$emails->member_id  = $this->user_id;
			$emails->payment_id = $payment_id;

			$site_name = stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_COMPAT, 'UTF-8' ) );

			$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $member->display_name . ' (' . $member->user_login . ') ' . __( 'just submitted a manual payment on', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Subscription level', 'rcp' ) . ': ' . $member->get_subscription_name() . "\n\n";
			$admin_message = apply_filters( 'rcp_before_admin_email_manual_payment_thanks', $admin_message, $this->user_id );
			$admin_message .= __( 'Thank you', 'rcp' );
			$admin_subject = sprintf( __( 'New manual payment on %s', 'rcp' ), $site_name );

			$emails->send( $admin_emails, $admin_subject, $admin_message );
		}

		wp_redirect( $this->return_url ); exit;

	}

}