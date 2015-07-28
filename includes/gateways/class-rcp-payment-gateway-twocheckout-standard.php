<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.2.3
*/

class RCP_Payment_Gateway_Twocheckout_Standard extends RCP_Payment_Gateway {

	private $secret_key;
	private $publishable_key;
	private $seller_id;
	private $environment;

	/**
	* get things going
	*
	* @since      2.2.3
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
			
		} else {

			$this->secret_key      = isset( $rcp_options['twocheckout_live_private'] )     ? trim( $rcp_options['twocheckout_live_private'] )     : '';
			$this->publishable_key = isset( $rcp_options['twocheckout_live_publishable'] ) ? trim( $rcp_options['twocheckout_live_publishable'] ) : '';
			$this->seller_id       = isset( $rcp_options['twocheckout_live_seller_id'] )   ? trim( $rcp_options['twocheckout_live_seller_id'] )   : '';
			$this->environment     = 'production';

		}

		if( ! class_exists( 'Twocheckout' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/twocheckout/Twocheckout.php';
		} 
	} // end init

	/**
	 * Process registration
	 *
	 * @since 2.2.3
	 */
	public function process_signup() {
	}

	/**
	 * Proccess webhooks
	 *
	 * @since 2.2.3
	 */
	public function process_webhooks() {
	}

	/**
	 * Process registration
	 *
	 * @since 2.2.3
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
				// show the errors on the form
				jQuery('#rcp_registration_form').unblock();
				jQuery('#rcp_submit').before( '<div class="rcp_message error"><p class="rcp_error"><span>' + response.error.message + '</span></p></div>' );
				jQuery('#rcp_submit').val( rcp_script_options.register );
		        // Set the token as the value for the token input
		        jQuery('#rcp_registration_form').token.value = data.response.token.token;
		        // IMPORTANT: Here we call `submit()` on the form element directly instead of using jQuery to prevent and infinite token request loop.
		        jQuery('#rcp_registration_form').submit();
		    };
		    // Called when token creation fails.
		    var errorCallback = function(data) {
		        if (data.errorCode === 200) {
		            tokenRequest();
		        } else {
		            alert(data.errorMsg);
		        }
		    };
		    var tokenRequest = function() {
		        // Setup token request arguments
		        var args = {
		            sellerId: '<?php echo $this->seller_id; ?>',
		            publishableKey: '<?php echo $this->publishable_key; ?>',
		            ccNo: $('.rcp_card_number').val(),
		            cvv: $('.rcp_card_cvc').val(),
		            expMonth: $('.rcp_card_exp_month').val(),
		            expYear: $('.rcp_card_exp_year').val()
		        };
		        // Make the token request
		        TCO.requestToken(successCallback, errorCallback, args);
		    };
		    jQuery(function() {
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
		rcp_get_template_part( 'card-form' );
		echo 'test';
		return ob_get_clean();
	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @since 2.2.3
	 */
	public function validate_fields() {

		if( empty( $_POST['rcp_card_number'] ) ) {
			rcp_errors()->add( 'missing_card_number', __( 'The card number you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_cvc'] ) ) {
			rcp_errors()->add( 'missing_card_code', __( 'The security code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_zip'] ) ) {
			rcp_errors()->add( 'missing_card_zip', __( 'The zip / postal code you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_name'] ) ) {
			rcp_errors()->add( 'missing_card_name', __( 'The card holder name you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_exp_month'] ) ) {
			rcp_errors()->add( 'missing_card_exp_month', __( 'The card expiration month you have entered is invalid', 'rcp' ), 'register' );
		}

		if( empty( $_POST['rcp_card_exp_year'] ) ) {
			rcp_errors()->add( 'missing_card_exp_year', __( 'The card expiration year you have entered is invalid', 'rcp' ), 'register' );
		}

	}

	/**
	 * Load 2Checkout JS
	 *
	 * @since 2.2.3
	 */
	public function scripts() {
		wp_enqueue_script( 'twocheckout', 'https://www.2checkout.com/checkout/api/2co.min.js', array( 'jquery' ) );
	}

	/**
	 * Create plan in Stripe
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function create_plan( $plan_name = '' ) {
	}
}