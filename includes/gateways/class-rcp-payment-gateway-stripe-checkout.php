<?php
/**
 * Payment Gateway For Stripe Checkout
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5
*/

class RCP_Payment_Gateway_Stripe_Checkout extends RCP_Payment_Gateway_Stripe {


	public function fields() {

		$data = array(
			'key'               => $this->publishable_key,
			'name'              => get_option( 'blogname' ),
			'local'             => 'auto',
			'allow-remember-me' => true,
		);

		$subscriptions = array();
		foreach ( rcp_get_subscription_levels( 'active' ) as $subscription ) {
			$subscriptions[ $subscription->id ] = array(
				'description' => $subscription->description,
				'label'       => 'Join ' . $subscription->name,
				'amount'      => '',
				'panelLabel'  => 'Register',
			);
		}

		ob_start(); ?>

		<script>
			var rcpSubscriptions = <?php echo json_encode( $subscriptions ); ?>;
			var checkoutArgs     = <?php echo json_encode( $data ); ?>;

			checkoutArgs.token = function(token){
				jQuery('#rcp_registration_form').append('<input type="hidden" name="stripeToken" value="' + token.id + '" />').submit();
			};

			var rcpStripeCheckout = StripeCheckout.configure(checkoutArgs);

			jQuery('#rcp_submit').on('click', function(e) {
				debugger;
				var level = jQuery(this).closest('form').find('input[name=rcp_level]:checked').val();

				// Open Checkout with further options
				rcpStripeCheckout.open(rcpSubscriptions[level]);
				e.preventDefault();

				return false;
			});

			// Close Checkout on page navigation
			jQuery(window).on('popstate', function() {
				rcpStripeCheckout.close();
			});
		</script>

		<?php
		return ob_get_clean();
	}

	/**
	 * Load Stripe JS
	 *
	 * @since 2.5
	 */
	public function scripts() {
		wp_enqueue_script( 'stripe-checkout', 'https://checkout.stripe.com/checkout.js', array( 'jquery' ) );

		parent::scripts();
	}

	/**
	 * Validate fields
	 *
	 * @since 2.5
	 */
	public function validate_fields() {}

}
