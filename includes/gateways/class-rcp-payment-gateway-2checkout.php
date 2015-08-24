<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
*/

class RCP_Payment_Gateway_2Checkout extends RCP_Payment_Gateway {

	private $secret_key;
	private $publishable_key;
	private $seller_id;
	private $environment;
	private $sandbox;

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

		$this->test_mode   = isset( $rcp_options['sandbox'] );

		if( $this->test_mode ) {

			$this->secret_key      = isset( $rcp_options['twocheckout_test_private'] )     ? trim( $rcp_options['twocheckout_test_private'] )     : '';
			$this->publishable_key = isset( $rcp_options['twocheckout_test_publishable'] ) ? trim( $rcp_options['twocheckout_test_publishable'] ) : '';
			$this->seller_id       = isset( $rcp_options['twocheckout_test_seller_id'] )   ? trim( $rcp_options['twocheckout_test_seller_id'] )   : '';
			$this->environment     = 'sandbox';
			$this->sandbox         = true;
			
		} else {

			$this->secret_key      = isset( $rcp_options['twocheckout_live_private'] )     ? trim( $rcp_options['twocheckout_live_private'] )     : '';
			$this->publishable_key = isset( $rcp_options['twocheckout_live_publishable'] ) ? trim( $rcp_options['twocheckout_live_publishable'] ) : '';
			$this->seller_id       = isset( $rcp_options['twocheckout_live_seller_id'] )   ? trim( $rcp_options['twocheckout_live_seller_id'] )   : '';
			$this->environment     = 'production';
			$this->sandbox         = false;

		}

		if( ! class_exists( 'Twocheckout' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/twocheckout/Twocheckout.php';
		} 
	} // end init

	/**
	 * Process registration
	 *
	 * @since 2.3
	 */
	public function process_signup() {

		require_once RCP_PLUGIN_DIR . 'includes/libraries/twocheckout/Twocheckout.php';

		Twocheckout::privateKey( $this->secret_key );
		Twocheckout::sellerId( $this->seller_id );
		Twocheckout::sandbox( $this->sandbox );

		$member       = new RCP_Member( $this->user_id );
		$subscription = rcp_get_subscription_details( $_POST['rcp_level'] );

		if( empty( $_POST['twoCheckoutToken'] ) ) {
			rcp_errors()->add( 'missing_card_token', __( 'Missing 2Checkout token, please try again or contact support if the issue persists.', 'rcp' ), 'register' );
			return;
		}

		try {

			$charge = Twocheckout_Charge::auth( array(
				'merchantOrderId' => $this->subscription_key,
				'token'           => $_POST['twoCheckoutToken'],
				'currency'        => strtolower( $this->currency ),
				'billingAddr'     => array(
					'name'        => sanitize_text_field( $_POST['rcp_card_name'] ),
					'addrLine1'   => sanitize_text_field( $_POST['rcp_card_address'] ),
					'city'        => sanitize_text_field( $_POST['rcp_card_city'] ),
					'state'       => sanitize_text_field( $_POST['rcp_card_state'] ),
					'zipCode'     => sanitize_text_field( $_POST['rcp_card_zip'] ),
					'country'     => sanitize_text_field( $_POST['rcp_card_country'] ),
					'email'       => $this->email,
				),
				"lineItems"       => array(
					array(
						"recurrence"  => $subscription->duration . ' ' . ucfirst( $subscription->duration_unit ),
						"type"        => 'product',
						"price"       => $this->amount,
						"productId"   => $subscription->id,
						"name"        => $subscription->name,
						"quantity"    => '1',
						"tangible"    => 'N',
						"startupFee"  => $subscription->fee,
						"description" => $subscription->description
					)
				),
			));

			if( $charge['response']['responseCode'] == 'APPROVED' ) {

				$charge_details = Twocheckout_Sale::retrieve( array(
					'api'        => true,
					'privateKey' => $this->secret_key,
					'sellerId'   => $this->seller_id,
					'sale_id'    => $charge['response']['transactionId']
				) );

				echo '<pre>'; print_r( $charge_details ); echo '</pre>'; exit;

				$payment_data = array(
					'date'              => date( 'Y-m-d g:i:s', current_time( 'timestamp' ) ),
					'subscription'      => $this->subscription_name,
					'payment_type' 		=> 'Credit Card One Time',
					'subscription_key' 	=> $this->subscription_key,
					'amount' 			=> $this->amount,
					'user_id' 			=> $this->user_id,
					'transaction_id'    => $charge['response']['transactionId']
				);

				$rcp_payments = new RCP_Payments();
				$rcp_payments->insert( $payment_data );

				// redirect to the success page, or error page if something went wrong
				wp_redirect( $this->return_url ); exit;
				
			}

		} catch ( Twocheckout_Error $e) {

			rcp_errors()->add( '2checkout_error', $e->getMessage(), 'register' );

		}
	}

	/**
	 * Proccess webhooks
	 *
	 * @since 2.3
	 */
	public function process_webhooks() {

		if ( isset( $_GET['listener'] ) && $_GET['listener'] == '2checkout' ) {

			global $wpdb;

			$hash  = strtoupper( md5( $_POST['sale_id'] . $this->seller_id . $_POST['invoice_id'] . $this->secret_key ) );

			if ( ! hash_equals( $hash, $_POST['md5_hash'] ) ) {
				edd_record_gateway_error( __( '2Checkout Error', 'rcp' ), sprintf( __( 'Invalid INS hash. INS data: %s', 'edd' ), json_encode( $_POST ) ) );
				die('-1');
			}

			if ( empty( $_POST['message_type'] ) ) {
				die( '-2' );
			}

			if ( empty( $_POST['vendor_id'] ) ) {
				die( '-3' );
			}

			$subscription_key = sanitize_text_field( $_POST['vendor_order_id'] );
			$member_id        = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'rcp_subscription_key' AND meta_value = %s LIMIT 1", $subscription_key ) );

			if ( ! $member_id ) {
				die( '-4' );
			}

			$member = new RCP_Member( $member_id );

			switch( strtoupper( $_POST['message_type'] ) ) {

				case 'ORDER_CREATED' :

					break;

				case 'REFUND_ISSUED' :

					break;

				case 'RECURRING_INSTALLMENT_SUCCESS' :

					break;

				case 'RECURRING_INSTALLMENT_FAILED' :

					break;

				case 'RECURRING_STOPPED' :

					break;

				case 'RECURRING_COMPLETE' :

					break;

				case 'RECURRING_RESTARTED' :

					break;


				case 'FRAUD_STATUS_CHANGED' :

					switch ( $_POST['fraud_status'] ) {
						case 'pass':
							break;
						case 'fail':
							break;
						case 'wait':
							break;
					}

					break;
			}

		}
	}

	/**
	 * Process registration
	 *
	 * @since 2.3
	 */
	public function fields() {
		ob_start();
		?>
		<script type="text/javascript">
			// Called when token created successfully.
			var successCallback = function(data) {
				// re-enable the submit button
				jQuery('#rcp_registration_form #rcp_submit').attr("disabled", false);
				// Remove loding overlay
				jQuery('#rcp_ajax_loading').hide();
				
				var form$ = jQuery('#rcp_registration_form');
				// token contains id, last4, and card type
				var token = data.response.token.token;
				// insert the token into the form so it gets submitted to the server
				form$.append("<input type='hidden' name='twoCheckoutToken' value='" + token + "' />");

				form$.get(0).submit();
			};
			// Called when token creation fails.
			var errorCallback = function(data) {
				if (data.errorCode === 200) {
					tokenRequest();
				} else {
					alert(data.errorMsg);
				}
				jQuery('#rcp_registration_form').unblock();
				jQuery('#rcp_submit').before( '<div class="rcp_message error"><p class="rcp_error"><span>' + data.reponerrorCode + '</span></p></div>' );
				jQuery('#rcp_submit').val( rcp_script_options.register );
			};
			var tokenRequest = function() {
				// Setup token request arguments
				var args = {
					sellerId: '<?php echo $this->seller_id; ?>',
					publishableKey: '<?php echo $this->publishable_key; ?>',
					ccNo: jQuery('.rcp_card_number').val(),
					cvv: jQuery('.rcp_card_cvc').val(),
					expMonth: jQuery('.rcp_card_exp_month').val(),
					expYear: jQuery('.rcp_card_exp_year').val()
				};
				// Make the token request
				TCO.requestToken(successCallback, errorCallback, args);
			};
			jQuery(document).ready(function($) {
				// Pull in the public encryption key for our environment
				TCO.loadPubKey('<?php echo $this->environment; ?>');
				jQuery("#rcp_registration_form").submit(function(e) {
					// Call our token request function
					tokenRequest();

					// Prevent form from submitting
					return false;
				});
			});
		</script>
		<?php
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

		if( empty( $_POST['rcp_card_state'] ) && $this->card_needs_state_and_zip() ) {
			rcp_errors()->add( 'missing_card_state', __( 'The state you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_country'] ) ) {
			rcp_errors()->add( 'missing_card_country', __( 'The country you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_zip'] ) && $this->card_needs_state_and_zip() ) {
			rcp_errors()->add( 'missing_card_zip', __( 'The zip / postal code you have entered is invalid', 'rcp' ), 'register' );
		}	

	}

	/**
	 * Load 2Checkout JS
	 *
	 * @since 2.3
	 */
	public function scripts() {
		wp_enqueue_script( 'twocheckout', 'https://www.2checkout.com/checkout/api/2co.min.js', array( 'jquery' ) );
	}

	/**
	 * Determine if zip / state are required
	 *
	 * @since 2.3
	 */
	private function card_needs_state_and_zip() {
	
		$ret = true;

		if( ! empty( $_POST['rcp_card_country'] ) ) {

			$needs_zip = array(
				'AR',
				'AU',
				'BG',
				'CA',
				'CH',
				'CY',
				'EG',
				'FR',
				'IN',
				'ID',
				'IT',
				'JP',
				'MY',
				'ME',
				'NL',
				'PA',
				'PH',
				'PO',
				'RO',
				'RU',
				'SR',
				'SG',
				'ZA',
				'ES',
				'SW',
				'TH',
				'TU',
				'GB',
				'US'
			);
			
			if( ! in_array( $_POST['rcp_card_country'], $needs_zip ) ) {
				$ret = false;
			}

		}

		return $ret;
	}
}