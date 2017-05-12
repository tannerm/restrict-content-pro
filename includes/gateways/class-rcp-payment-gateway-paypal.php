<?php
/**
 * PayPal Standard Payment Gateway
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/PayPal
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal extends RCP_Payment_Gateway {

	private $api_endpoint;
	private $checkout_url;
	protected $username;
	protected $password;
	protected $signature;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		global $rcp_options;

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';
		$this->supports[] = 'trial';

		if( $this->test_mode ) {

			$this->api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
			$this->checkout_url = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';

		} else {

			$this->api_endpoint = 'https://api-3t.paypal.com/nvp';
			$this->checkout_url = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';

		}

		if( rcp_has_paypal_api_access() ) {

			$creds = rcp_get_paypal_api_credentials();

			$this->username  = $creds['username'];
			$this->password  = $creds['password'];
			$this->signature = $creds['signature'];

		}

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

		if( $this->test_mode ) {
			$paypal_redirect = 'https://www.sandbox.paypal.com/cgi-bin/webscr/?';
		} else {
			$paypal_redirect = 'https://www.paypal.com/cgi-bin/webscr/?';
		}

		// Setup PayPal arguments
		$paypal_args = array(
			'business'      => trim( $rcp_options['paypal_email'] ),
			'email'         => $this->email,
			'item_number'   => $this->subscription_key,
			'item_name'     => $this->subscription_name,
			'no_shipping'   => '1',
			'shipping'      => '0',
			'no_note'       => '1',
			'currency_code' => $this->currency,
			'charset'       => get_bloginfo( 'charset' ),
			'custom'        => $this->user_id,
			'rm'            => '2',
			'return'        => $this->return_url,
			'cancel_return' => home_url(),
			'notify_url'    => add_query_arg( 'listener', 'IPN', home_url( 'index.php' ) ),
			'cbt'			=> get_bloginfo( 'name' ),
			'tax'           => 0,
			'page_style'    => ! empty( $rcp_options['paypal_page_style'] ) ? trim( $rcp_options['paypal_page_style'] ) : '',
			'bn'            => 'EasyDigitalDownloads_SP'
		);

		// recurring paypal payment
		if( $this->auto_renew && ! empty( $this->length ) ) {

			// recurring paypal payment
			$paypal_args['cmd'] = '_xclick-subscriptions';
			$paypal_args['src'] = '1';
			$paypal_args['sra'] = '1';
			$paypal_args['a3'] = $this->amount;
			$paypal_args['a1'] = $this->initial_amount;

			$paypal_args['p3'] = $this->length;
			$paypal_args['p1'] = $this->length;

			switch ( $this->length_unit ) {

				case "day" :

					$paypal_args['t3'] = 'D';
					$paypal_args['t1'] = 'D';
					break;

				case "month" :

					$paypal_args['t3'] = 'M';
					$paypal_args['t1'] = 'M';
					break;

				case "year" :

					$paypal_args['t3'] = 'Y';
					$paypal_args['t1'] = 'Y';
					break;

			}

			if ( $this->is_trial() ) {

				$paypal_args['a1'] = 0;
				$paypal_args['p1'] = $this->subscription_data['trial_duration'];

				switch ( $this->subscription_data['trial_duration_unit'] ) {

					case 'day':
						$paypal_args['t1'] = 'D';
						break;

					case 'month':
						$paypal_args['t1'] = 'M';
						break;

					case 'year':
						$paypal_args['t1'] = 'Y';
						break;
				}
			}

		} else {

			// one time payment
			$paypal_args['cmd'] = '_xclick';
			$paypal_args['amount'] = $this->initial_amount;

		}

		$paypal_args = apply_filters( 'rcp_paypal_args', $paypal_args, $this );

		// Build query
		$paypal_redirect .= http_build_query( $paypal_args );

		// Fix for some sites that encode the entities
		$paypal_redirect = str_replace( '&amp;', '&', $paypal_redirect );

		// Redirect to paypal
		header( 'Location: ' . $paypal_redirect );
		exit;

	}

	/**
	 * Process PayPal IPN
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'IPN' ) {
			return;
		}

		global $rcp_options;

		nocache_headers();

		if( ! class_exists( 'IpnListener' ) ) {
			// instantiate the IpnListener class
			include( RCP_PLUGIN_DIR . 'includes/gateways/paypal/paypal-ipnlistener.php' );
		}

		$listener = new IpnListener();
		$verified = false;

		if( $this->test_mode ) {
			$listener->use_sandbox = true;
		}

		/*
		if( isset( $rcp_options['ssl'] ) ) {
			$listener->use_ssl = true;
		} else {
			$listener->use_ssl = false;
		}
		*/

		//To post using the fsockopen() function rather than cURL, use:
		if( isset( $rcp_options['disable_curl'] ) ) {
			$listener->use_curl = false;
		}

		try {
			$listener->requirePostMethod();
			$verified = $listener->processIpn();
		} catch ( Exception $e ) {
			status_header( 402 );
			//die( 'IPN exception: ' . $e->getMessage() );
		}

		/*
		The processIpn() method returned true if the IPN was "VERIFIED" and false if it
		was "INVALID".
		*/
		if ( $verified || isset( $_POST['verification_override'] ) || ( $this->test_mode || isset( $rcp_options['disable_ipn_verify'] ) ) )  {

			status_header( 200 );

			$user_id = 0;
			$posted  = apply_filters('rcp_ipn_post', $_POST ); // allow $_POST to be modified

			if( ! empty( $posted['subscr_id'] ) ) {

				$user_id = rcp_get_member_id_from_profile_id( $posted['subscr_id'] );

			}

			if( empty( $user_id ) && ! empty( $posted['custom'] ) && is_numeric( $posted['custom'] ) ) {

				$user_id = absint( $posted['custom'] );

			}

			if( empty( $user_id ) && ! empty( $posted['payer_email'] ) ) {

				$user    = get_user_by( 'email', $posted['payer_email'] );
				$user_id = $user ? $user->ID : false;

			}

			$member = new RCP_Member( $user_id );

			if( ! $member || ! $member->ID > 0 ) {
				die( 'no member found' );
			}

			$subscription_id = $member->get_pending_subscription_id();

			if( empty( $subscription_id ) ) {

				$subscription_id = $member->get_subscription_id();

			}

			if( ! $subscription_id ) {
				die( 'no subscription for member found' );
			}

			if( ! rcp_get_subscription_details( $subscription_id ) ) {
				die( 'no subscription level found' );
			}

			$subscription_name  = $posted['item_name'];
			$subscription_key   = $posted['item_number'];
			$has_trial          = ! empty( $posted['period1'] );
			$amount             = ! $has_trial ? number_format( (float) $posted['mc_gross'], 2 ) : number_format( (float) $posted['mc_amount1'], 2 );
			// not used anywhere?
			// $amount2            = number_format( (float) $posted['mc_amount3'], 2 );
			$payment_status     = ! empty( $posted['payment_status'] ) ? $posted['payment_status'] : false;
			$currency_code      = $posted['mc_currency'];
			$subscription_price = number_format( (float) rcp_get_subscription_price( $subscription_id ), 2 );

			$pending_amount = get_user_meta( $member->ID, 'rcp_pending_subscription_amount', true );
			$pending_amount = number_format( (float) $pending_amount, 2 );

			// Check for invalid amounts in the IPN data
			if ( ! empty( $pending_amount ) && ! empty( $amount ) && in_array( $posted['txn_type'], array( 'web_accept', 'subscr_payment' ) ) ) {

				if ( $amount < $pending_amount ) {

					rcp_add_member_note( $member->ID, sprintf( __( 'Incorrect amount received in the IPN. Amount received was %s. The amount should have been %s. PayPal Transaction ID: %s', 'rcp' ), $amount, $pending_amount, sanitize_text_field( $posted['txn_id'] ) ) );

					die( 'incorrect amount' );

				} else {
					delete_user_meta( $member->ID, 'rcp_pending_subscription_amount' );
				}

			}

			$rcp_payments = new RCP_Payments();

			// setup the payment info in an array for storage
			$payment_data = array(
				'date'             => ! empty( $posted['payment_date'] ) ? date( 'Y-m-d H:i:s', strtotime( $posted['payment_date'], current_time( 'timestamp' ) ) ) : date( 'Y-m-d H:i:s', strtotime( 'now', current_time( 'timestamp' ) ) ),
				'subscription'     => $posted['item_name'],
				'payment_type'     => $posted['txn_type'],
				'subscription_key' => $subscription_key,
				'amount'           => $amount,
				'user_id'          => $user_id,
				'transaction_id'   => ! empty( $posted['txn_id'] ) ? $posted['txn_id'] : false
			);

			do_action( 'rcp_valid_ipn', $payment_data, $user_id, $posted );

			if( $posted['txn_type'] == 'web_accept' || $posted['txn_type'] == 'subscr_payment' ) {

				// only check for an existing payment if this is a payment IPD request
				if( ! empty( $posted['txn_id'] ) && $rcp_payments->payment_exists( $posted['txn_id'] ) ) {

					$log_data = array(
						'post_title'    => __( 'Duplicate Payment', 'rcp' ),
						'post_content'  =>  __( 'A duplicate payment was detected. The new payment was still recorded, so you may want to check into both payments.', 'rcp' ),
						'post_parent'   => 0,
						'log_type'      => 'gateway_error'
					);

					$log_meta = array(
						'user_subscription' => $posted['item_name'],
						'user_id'           => $user_id
					);
					$log_entry = WP_Logging::insert_log( $log_data, $log_meta );


					die( 'duplicate IPN detected' );
				}

				if( ! rcp_is_valid_currency( $currency_code ) ) {
					// the currency code is invalid

					$log_data = array(
						'post_title'    => __( 'Invalid Currency Code', 'rcp' ),
						'post_content'  =>  sprintf( __( 'The currency code in an IPN request did not match the site currency code. Payment data: %s', 'rcp' ), json_encode( $payment_data ) ),
						'post_parent'   => 0,
						'log_type'      => 'gateway_error'
					);

					$log_meta = array(
						'user_subscription' => $posted['item_name'],
						'user_id'           => $user_id
					);
					$log_entry = WP_Logging::insert_log( $log_data, $log_meta );


					die( 'invalid currency code' );
				}

			}

			if( isset( $rcp_options['email_ipn_reports'] ) ) {
				wp_mail( get_bloginfo('admin_email'), __( 'IPN report', 'rcp' ), $listener->getTextReport() );
			}

			/* now process the kind of subscription/payment */

			// Subscriptions
			switch ( $posted['txn_type'] ) :

				case "subscr_signup" :
					// when a new user signs up

					// store the recurring payment ID
					update_user_meta( $user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

					if( $member->just_upgraded() && $member->can_cancel() ) {
						$cancelled = $member->cancel_payment_profile( false );
					}

					$member->set_payment_profile_id( $posted['subscr_id'] );

					if ( $has_trial ) {
						$trial_expires = $member->calculate_expiration( true, true );
						$member->set_expiration_date( $trial_expires );
						$member->set_status( 'active' );
						$member->set_recurring( true );
					}

					do_action( 'rcp_ipn_subscr_signup', $user_id );


					die( 'successful subscr_signup' );

					break;

				case "subscr_payment" :

					// when a user makes a recurring payment

					update_user_meta( $user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

					$member->set_payment_profile_id( $posted['subscr_id'] );

					$member->renew( true );

					// record this payment in the database
					$rcp_payments->insert( $payment_data );

					do_action( 'rcp_ipn_subscr_payment', $user_id );


					die( 'successful subscr_payment' );

					break;

				case "subscr_cancel" :

					if( ! $member->just_upgraded() ) {

						// user is marked as cancelled but retains access until end of term
						$member->cancel();

						// set the use to no longer be recurring
						delete_user_meta( $user_id, 'rcp_paypal_subscriber' );

						do_action( 'rcp_ipn_subscr_cancel', $user_id );

						die( 'successful subscr_cancel' );

					}

					break;

				case "subscr_failed" :

					if ( ! empty( $posted['txn_id'] ) ) {

						$this->webhook_event_id = sanitize_text_field( $posted['txn_id'] );

					} elseif ( ! empty( $posted['ipn_track_id'] ) ) {

						$this->webhook_event_id = sanitize_text_field( $posted['ipn_track_id'] );
					}

					do_action( 'rcp_recurring_payment_failed', $member, $this );
					do_action( 'rcp_ipn_subscr_failed' );

					die( 'successful subscr_failed' );

					break;

				case "subscr_eot" :

					// user's subscription has reached the end of its term

					if( isset( $posted['subscr_id'] ) && $posted['subscr_id'] == $member->get_payment_profile_id() && 'cancelled' !== $member->get_status() ) {

						$member->set_status( 'expired' );

					}

					do_action('rcp_ipn_subscr_eot', $user_id );


					die( 'successful subscr_eot' );

					break;

				case "web_accept" :

					switch ( strtolower( $payment_status ) ) :

						case 'completed' :

							if( $member->just_upgraded() && $member->can_cancel() ) {
								$cancelled = $member->cancel_payment_profile( false );
								if( $cancelled ) {

									$member->set_payment_profile_id( '' );

								}
							}

							// set this user to active
							$member->renew();

							$rcp_payments->insert( $payment_data );

							break;

						case 'denied' :
						case 'expired' :
						case 'failed' :
						case 'voided' :
							$member->cancel();
							break;

					endswitch;


					die( 'successful web_accept' );

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

			status_header( 400 );
			die( 'invalid IPN' );

		}

	}

}