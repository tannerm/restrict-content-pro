<?php
/**
 * Payment Gateway Authorize.net Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
*/

class RCP_Payment_Gateway_Authorizenet extends RCP_Payment_Gateway {

	private $md5_hash_value;
	private $api_login_id;
	private $transaction_key;

	/**
	* get things going
	*
	* @since      2.7
	*/
	public function init() {
		global $rcp_options;

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->secret_word = isset( $rcp_options['twocheckout_secret_word'] ) ? trim( $rcp_options['twocheckout_secret_word'] ) : '';

		// Load Authorize SDK and define its contants

		if( ! class_exists( 'AuthnetXML' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/authorize/AuthnetXML/AuthnetXML.class.php';
		}

		$this->api_login_id    = isset( $rcp_options['authorize_api_login'] )  ? sanitize_text_field( $rcp_options['authorize_api_login'] )  : '';
		$this->transaction_key = isset( $rcp_options['authorize_txn_key'] )    ? sanitize_text_field( $rcp_options['authorize_txn_key'] )    : '';
		$this->md5_hash_value  = isset( $rcp_options['authorize_hash_value'] ) ? sanitize_text_field( $rcp_options['authorize_hash_value'] ) : '';

	} // end init

	/**
	 * Process registration
	 *
	 * @since 2.7
	 */
	public function process_signup() {

		if ( ! class_exists( 'AuthnetXML' ) ) {
			rcp_errors()->add( 'missing_api_files', __( 'Missing Authorize.net API files, please try again or contact support if the issue persists.', 'rcp' ), 'register' );
		}

		if ( empty( $this->api_login_id ) || empty( $this->transaction_key ) ) {
			rcp_errors()->add( 'missing_authorize_settings', __( 'Authorize.net API Login ID or Transaction key is missing.', 'rcp' ) );
		}

		$member = new RCP_Member( $this->user_id );

		$paid = false;

		// Set date to same timezone as Authorize's servers (Mountain Time) to prevent conflicts
		date_default_timezone_set( 'America/Denver' );

		$length = $this->length . 's';
		$unit   = $this->length_unit;

		if( 'years' == $unit && 1 == $length ) {
			$unit   = 'months';
			$length = 12;
		}

		$args = array(
			'subscription' 	=> array(
				'name'            => $this->subscription_name . ' - ' . $this->subscription_id,
				'paymentSchedule' => array(
					'interval'         => array(
						'length' => $length,
						'unit'   => $unit,
					),
					'startDate'        => date( 'Y-m-d' ),
					'totalOccurrences' => $this->auto_renew ? 9999 : 1,
					'trialOccurrences' => 1, // TODO update with free trial support
				),
				'amount'      => $this->amount,
				'trialAmount' => $this->initial_amount,
				'payment'     => array(
					'creditCard' => array(
						'cardNumber'     => sanitize_text_field( $_POST['rcp_card_number'] ),
						'expirationDate' => sanitize_text_field( $_POST['rcp_card_exp_year'] ) . '-' . sanitize_text_field( $_POST['rcp_card_exp_month'] ),
						'cardCode'       => sanitize_text_field( $_POST['rcp_card_cvc'] ),
					),
				),
				'billTo'      => array(
					'firstName' => sanitize_text_field( $_POST['rcp_user_first'] ),
					'lastName'  => sanitize_text_field( $_POST['rcp_user_last'] ),
					'zip'       => sanitize_text_field( $_POST['rcp_card_zip'] ),
				),
			),
		);

		$authnet_xml = new AuthnetXML( $this->api_login_id, $this->transaction_key, $this->test_mode );
		$authnet_xml->ARBCreateSubscriptionRequest( $args );

		if ( $authnet_xml->isSuccessful() ) {

			// set this user to active
			$member->renew( $this->auto_renew );
			$member->add_note( __( 'Subscription started in Authorize.net', 'rcp' ) );

			$member->set_payment_profile_id( $authnet_xml->subscriptionId );

			if ( ! is_user_logged_in() ) {

				// log the new user in
				rcp_login_user_in( $this->user_id, $this->user_name, $_POST['rcp_user_pass'] );

			}

			do_action( 'rcp_authorizenet_signup', $this->user_id, $this );

		} else {

			if( isset( $authnet_xml->messages->message ) ) {

				$error = $authnet_xml->messages->message->code . ': ' . $authnet_xml->messages->message->text;

			} else {

				$error = __( 'Your subscription cannot be created due to an error at the gateway.', 'rcp' );

			}

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		}

		// redirect to the success page, or error page if something went wrong
		wp_redirect( $this->return_url ); exit;
	}

	/**
	 * Proccess webhooks
	 *
	 * @since 2.7
	 */
	public function process_webhooks() {

		global $rcp_payments_db;

		if ( empty( $_GET['listener'] ) || 'authnet' != $_GET['listener'] ) {
			return;
		}

		if( ! $this->is_silent_post_valid( $_POST ) ) {
			die( 'invalid silent post' );
		}

		$anet_subscription_id = intval( $_POST['x_subscription_id'] );

		if ( $anet_subscription_id ) {

			$response_code = intval( $_POST['x_response_code'] );
			$reason_code   = intval( $_POST['x_response_reason_code'] );

			$member_id = rcp_get_member_id_from_profile_id( $anet_subscription_id );

			if( empty( $member_id ) ) {
				die( 'no member found' );
			}

			$member = new RCP_Member( $member_id );

			if ( 1 == $response_code ) {

				// Approved
				$renewal_amount = sanitize_text_field( $_POST['x_amount'] );
				$transaction_id = sanitize_text_field( $_POST['x_trans_id'] );

				$payment_data = array(
					'date'             => date( 'Y-m-d H:i:s', strtotime( $_POST['timestamp'], current_time( 'timestamp' ) ) ),
					'subscription'     => $member->get_subscription_name(),
					'payment_type'     => 'Credit Card',
					'subscription_key' => $member->subscription_key,
					'amount'           => $renewal_amount,
					'user_id'          => $member->ID,
					'transaction_id'   => $transaction_id
				);

				$member->renew( $true );
				$payments->insert( $payment_data );
				$member->add_note( __( 'Subscription renewed in Authorize.net', 'rcp' ) );

				do_action( 'rcp_authorizenet_silent_post_payment', $member, $this );

			} elseif ( 2 == $response_code ) {

				// Declined
				do_action( 'rcp_recurring_payment_failed', $member, $this );
				do_action( 'rcp_authorizenet_silent_post_error', $member, $this );

			} elseif ( 3 == $response_code || 8 == $reason_code ) {

				// An expired card
				do_action( 'rcp_recurring_payment_failed', $member, $this );
				do_action( 'rcp_authorizenet_silent_post_error', $member, $this );

			} else {

				// Other Error
				do_action( 'rcp_authorizenet_silent_post_error', $member, $this );

			}
		}

		die( 'success');
	}

	/**
	 * Load credit card fields
	 *
	 * @since 2.7
	 */
	public function fields() {
		ob_start();
		rcp_get_template_part( 'card-form', 'full' );
		return ob_get_clean();
	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since 2.7
	 */
	public function validate_fields() {

		if( empty( $_POST['rcp_card_cvc'] ) ) {
			rcp_errors()->add( 'missing_card_code', __( 'The security code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_zip'] ) ) {
			rcp_errors()->add( 'missing_card_zip', __( 'Please enter a Zip / Postal Code code', 'rcp' ), 'register' );
		}

	}


	/**
	 * Determines if the silent post is valid by verifying the MD5 Hash
	 *
	 * @access  public
	 * @since   2.7
	 * @param   array $request The Request array containing data for the silent post
	 * @return  bool
	 */
	public function is_silent_post_valid( $request ) {

		$auth_md5 = isset( $request['x_MD5_Hash'] ) ? $request['x_MD5_Hash'] : '';

		//Sanity check to ensure we have an MD5 Hash from the silent POST
		if( empty( $auth_md5 ) ) {
			return false;
		}

		$str           = $this->md5_hash_value . $request['x_trans_id'] . $request['x_amount'];
		$generated_md5 = strtoupper( md5( $str ) );

		return hash_equals( $generated_md5, $auth_md5 );
	}

}
