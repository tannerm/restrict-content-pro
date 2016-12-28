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
	* @since      2.3
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
	 * @since 2.3
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

		if ( $this->auto_renew ) {


		} else {

		

		}

		if ( $paid ) {

			// set this user to active
			$member->renew( $this->auto_renew );
			$member->add_note( __( 'Subscription started in Authorize.net', 'rcp' ) );

			$member->set_payment_profile_id( );

			if ( ! is_user_logged_in() ) {

				// log the new user in
				rcp_login_user_in( $this->user_id, $this->user_name, $_POST['rcp_user_pass'] );

			}

			do_action( 'rcp_authorizenet_signup', $this->user_id, $this );

		}

		// redirect to the success page, or error page if something went wrong
		wp_redirect( $this->return_url ); exit;
	}

	/**
	 * Proccess webhooks
	 *
	 * @since 2.3
	 */
	public function process_webhooks() {

		if ( isset( $_GET['listener'] ) && $_GET['listener'] == 'authnet' ) {

			global $wpdb;

			die( 'success');
		}
	}

	/**
	 * Process registration
	 *
	 * @since 2.3
	 */
	public function fields() {
		ob_start();
		rcp_get_template_part( 'card-form', 'full' );
		return ob_get_clean();
	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since 2.3
	 */
	public function validate_fields() {

		if( empty( $_POST['rcp_card_cvc'] ) ) {
			rcp_errors()->add( 'missing_card_code', __( 'The security code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_address'] ) ) {
			rcp_errors()->add( 'missing_card_address', __( 'The address you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_city'] ) ) {
			rcp_errors()->add( 'missing_card_city', __( 'The city you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_country'] ) ) {
			rcp_errors()->add( 'missing_card_country', __( 'The country you have entered is invalid', 'rcp' ), 'register' );
		}

	}

	/**
	 * Load 2Checkout JS
	 *
	 * @since 2.3
	 */
	public function scripts() {

	}

}
