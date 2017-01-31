<?php
/**
 * Braintree Payment Gateway Class
 *
 * @package Restrict Content Pro
 * @since 2.7
 */

class RCP_Payment_Gateway_Braintree extends RCP_Payment_Gateway {

	private $merchantId;
	private $publicKey;
	private $privateKey;
	private $encryptionKey;


// @todo only load if PHP 5.4+

	public function init() {

		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			return;
		}

		global $rcp_options;

		$this->supports[] = 'one-time';
		$this->supports[] = 'recurring';
		$this->supports[] = 'fees';
		$this->supports[] = 'trial';

		if ( $this->test_mode ) {
			$this->merchantId    = ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_merchantId'] ) : '';
			$this->publicKey     = ! empty( $rcp_options['braintree_sandbox_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_publicKey'] ) : '';
			$this->privateKey    = ! empty( $rcp_options['braintree_sandbox_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_privateKey'] ) : '';
			$this->encryptionKey = ! empty( $rcp_options['braintree_sandbox_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_encryptionKey'] ) : '';
		} else {
			$this->merchantId    = ! empty( $rcp_options['braintree_live_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_live_merchantId'] ) : '';
			$this->publicKey     = ! empty( $rcp_options['braintree_live_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_publicKey'] ) : '';
			$this->privateKey    = ! empty( $rcp_options['braintree_live_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_privateKey'] ) : '';
			$this->encryptionKey = ! empty( $rcp_options['braintree_live_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_encryptionKey'] ) : '';
		}

		require_once RCP_PLUGIN_DIR . 'includes/libraries/braintree/lib/Braintree.php';

	}

	public function validate_fields() {

	}

	public function process_signup() {


	}

	public function process_webhooks() {

	}

	public function fields() {
		ob_start();
		?>
		<fieldset class="rcp_card_fieldset">
			<p id="rcp_card_number_wrap">
				<label><?php _e( 'Card Number', 'rcp' ); ?></label>
				<input data-braintree-name="number" value="">
			</p>

			<p id="rcp_card_cvc_wrap">
				<label><?php _e( 'Card CVC', 'rcp' ); ?></label>
				<input data-braintree-name="cvv" value="">
			</p>

			<p id="rcp_card_zip_wrap">
				<label><?php _e( 'Card ZIP or Postal Code', 'rcp' ); ?></label>
				<input data-braintree-name="postal_code" value="">
			</p>

			<p id="rcp_card_name_wrap">
				<label><?php _e( 'Name on Card', 'rcp' ); ?></label>
				<input data-braintree-name="cardholder_name" value="">
			</p>

			<p id="rcp_card_exp_wrap">
				<label><?php _e( 'Expiration (MM/YYYY)', 'rcp' ); ?></label>
				<select data-braintree-name="expiration_month" class="rcp_card_exp_month card-expiry-month">
					<?php for( $i = 1; $i <= 12; $i++ ) : ?>
						<option value="<?php echo $i; ?>"><?php echo $i . ' - ' . rcp_get_month_name( $i ); ?></option>
					<?php endfor; ?>
				</select>

				<span class="rcp_expiry_separator"> / </span>

				<select data-braintree-name="expiration_year" class="rcp_card_exp_year card-expiry-year">
					<?php
					$year = date( 'Y' );
					for( $i = $year; $i <= $year + 10; $i++ ) : ?>
						<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
					<?php endfor; ?>
				</select>
			</p>
		</fieldset>
		<?php
		return ob_get_clean();
	}

	public function scripts() {
		wp_enqueue_script( 'braintree', 'https://js.braintreegateway.com/js/braintree-2.30.0.min.js' );
		?>
		<script type="text/javascript">
			// test
			document.addEventListener('DOMContentLoaded', function() {
				console.log(braintree);
			});
		</script>
		<?php
	}
}