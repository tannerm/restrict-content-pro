<?php
/**
 * PayPal Express Gateway class
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal_Express extends RCP_Payment_Gateway {

	public $id;

	public function init() {

		global $rcp_options;

		$this->id          = 'paypal_express';
		$this->title       = 'PayPal Express';
		$this->description = 'It is PayPal, what else?';
		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->test_mode   = isset( $rcp_options['sandbox'] );

	}

	public function process_signup() {

		

	}

	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'EIPN' ) {
			return;
		}

		global $rcp_options;

		if( ! class_exists( 'IpnListener' ) ) {
			// instantiate the IpnListener class
			include( RCP_PLUGIN_DIR . 'includes/gateways/paypal-ipnlistener.php' );
		}

		$listener = new IpnListener();

		if( $this->test_mode ) {
			$listener->use_sandbox = true;
		}

		if( isset( $rcp_options['ssl'] ) ) {
			$listener->use_ssl = true;
		} else {
			$listener->use_ssl = false;
		}

		//To post using the fsockopen() function rather than cURL, use:
		if( isset( $rcp_options['disable_curl'] ) )
			$listener->use_curl = false;

		try {
			$listener->requirePostMethod();
			$verified = $listener->processIpn();
		} catch ( Exception $e ) {
			//exit(0);
		}

		/*
		The processIpn() method returned true if the IPN was "VERIFIED" and false if it
		was "INVALID".
		*/
		if ( $verified || isset( $_POST['verification_override'] ) || ( $this->test_mode || isset( $rcp_options['disable_ipn_verify'] ) ) )  {

			$posted        = apply_filters('rcp_ipn_post', $_POST ); // allow $_POST to be modified

			$this->user_id = absint( $posted['custom'] );
			$member        = new RCP_Member( $this->user_id );

			if( ! $member || ! $member->get_subscription_id() ) {
				return;
			}

			if( ! rcp_get_subscription_details( $member->get_subscription_id() ) ) {
				return;
			}

			$subscription_name 	= $posted['item_name'];
			$subscription_key 	= $posted['item_number'];
			$amount 			= number_format( (float) $posted['mc_gross'], 2 );
			$amount2 			= number_format( (float) $posted['mc_amount3'], 2 );
			$payment_status 	= $posted['payment_status'];
			$currency_code		= $posted['mc_currency'];
			$subscription_price = number_format( (float) rcp_get_subscription_price( $member->get_subscription_id() ), 2 );

			// setup the payment info in an array for storage
			$payment_data = array(
				'date'             => date( 'Y-m-d g:i:s', strtotime( $posted['payment_date'] ) ),
				'subscription'     => $posted['item_name'],
				'payment_type'     => $posted['txn_type'],
				'subscription_key' => $subscription_key,
				'amount'           => $amount,
				'user_id'          => $this->user_id,
				'transaction_id'   => $posted['txn_id']
			);

			do_action( 'rcp_valid_ipn', $payment_data, $this->user_id, $posted );

			if( $posted['txn_type'] == 'web_accept' || $posted['txn_type'] == 'subscr_payment' ) {

				// only check for an existing payment if this is a payment IPD request
				if( rcp_check_for_existing_payment( $posted['txn_type'], $posted['payment_date'], $subscription_key ) ) {

					$log_data = array(
					    'post_title'    => __( 'Duplicate Payment', 'rcp' ),
					    'post_content'  =>  __( 'A duplicate payment was detected. The new payment was still recorded, so you may want to check into both payments.', 'rcp' ),
					    'post_parent'   => 0,
					    'log_type'      => 'gateway_error'
					);

					$log_meta = array(
					    'user_subscription' => $posted['item_name'],
					    'user_id'           => $this->user_id
					);
					$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

					return; // this IPN request has already been processed
				}

				if( strtolower( $currency_code ) != strtolower( $rcp_options['currency'] ) ) {
					// the currency code is invalid

					$log_data = array(
					    'post_title'    => __( 'Invalid Currency Code', 'rcp' ),
					    'post_content'  =>  sprintf( __( 'The currency code in an IPN request did not match the site currency code. Payment data: %s', 'rcp' ), json_encode( $payment_data ) ),
					    'post_parent'   => 0,
					    'log_type'      => 'gateway_error'
					);

					$log_meta = array(
					    'user_subscription' => $posted['item_name'],
					    'user_id'           => $this->user_id
					);
					$log_entry = WP_Logging::insert_log( $log_data, $log_meta );

					return;
				}

			}

			if( isset( $rcp_options['email_ipn_reports'] ) ) {
				wp_mail( get_bloginfo('admin_email'), __( 'IPN report', 'rcp' ), $listener->getTextReport() );
			}

			/* now process the kind of subscription/payment */

			$rcp_payments = new RCP_Payments();

			// Subscriptions
			switch ( $posted['txn_type'] ) :

				case "subscr_signup" :
					// when a new user signs up

					// store the recurring payment ID
					update_user_meta( $this->user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

					$member->set_payment_profile_id( $posted['subscr_id'] );

					do_action( 'rcp_ipn_subscr_signup', $this->user_id );

					break;

				case "subscr_payment" :

					// when a user makes a recurring payment

					// record this payment in the database
					$rcp_payments->insert( $payment_data );

					update_user_meta( $this->user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

					$member->set_payment_profile_id( $posted['subscr_id'] );

					$this->renew_member( true );

					do_action( 'rcp_ipn_subscr_payment', $this->user_id );

					break;

				case "subscr_cancel" :

					// user is marked as cancelled but retains access until end of term
					$member->set_status( 'cancelled' );

					// set the use to no longer be recurring
					delete_user_meta( $this->user_id, 'rcp_paypal_subscriber' );

					do_action( 'rcp_ipn_subscr_cancel', $this->user_id );

					break;

				case "subscr_failed" :

					do_action( 'rcp_ipn_subscr_failed' );
					break;

				case "subscr_eot" :

					// user's subscription has reached the end of its term

					if( 'cancelled' !== $member->get_status( $this->user_id ) ) {

						$member->set_status( 'expired' );

					}

					do_action('rcp_ipn_subscr_eot', $this->user_id );

					break;

				case "web_accept" :

					switch ( strtolower( $payment_status ) ) :

			            case 'completed' :

							// set this user to active
							$this->renew_member();

							$rcp_payments->insert( $payment_data );

			           		break;

			            case 'denied' :
			            case 'expired' :
			            case 'failed' :
			            case 'voided' :
							$member->set_status( 'cancelled' );
			            	break;

			        endswitch;

				break;

			case "cart" :
			case "express_checkout" :
			default :

				break;

			endswitch;

		} else {

			if( isset( $rcp_options['email_ipn_reports'] ) ) {
				// an invalid IPN attempt was made. Send an email to the admin account to investigate
				wp_mail( get_bloginfo( 'admin_email' ), __( 'Invalid IPN', 'rcp' ), $listener->getTextReport() );
			}

		}

	}

}