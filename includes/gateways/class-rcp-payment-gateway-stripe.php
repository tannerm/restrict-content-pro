<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_Stripe extends RCP_Payment_Gateway {

	public $id;
	private $secret_key;
	private $publishable_key;

	public function init() {

		global $rcp_options;

		$this->id          = 'stripe';
		$this->title       = 'Stripe';
		$this->description = __( 'Pay with a credit or debit card', 'rcp' );
		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->test_mode   = isset( $rcp_options['sandbox'] );

		if( $this->test_mode ) {

			$this->secret_key      = isset( $rcp_options['stripe_test_secret'] )      ? trim( $rcp_options['stripe_test_secret'] )      : '';
			$this->publishable_key = isset( $rcp_options['stripe_test_publishable'] ) ? trim( $rcp_options['stripe_test_publishable'] ) : '';

		} else {

			$this->secret_key      = isset( $rcp_options['stripe_live_secret'] )      ? trim( $rcp_options['stripe_live_secret'] )      : '';
			$this->publishable_key = isset( $rcp_options['stripe_live_publishable'] ) ? trim( $rcp_options['stripe_live_publishable'] ) : '';

		}

		if( ! class_exists( 'Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/Stripe.php';
		}

	}

	public function process_signup() {

		Stripe::setApiKey( $this->secret_key );

		$paid   = false;
		$member = new RCP_Member( $this->user_id );
		$customer_exists = false;

		if ( $this->auto_renew ) {

			// process a subscription sign up

			$plan_id = strtolower( str_replace( ' ', '', $this->subscription_name ) );

			if ( ! $this->plan_exists( $plan_id ) ) {
				// create the plan if it doesn't exist
				$this->create_plan( $this->subscription_name );
			}

			try {

				$customer_id = $member->get_payment_profile_id();

				if ( $customer_id ) {

					$customer_exists = true;

					try {

						// Update the customer to ensure their card data is up to date
						$customer = Stripe_Customer::retrieve( $customer_id );

						if( isset( $customer->deleted ) && $customer->deleted ) {

							// This customer was deleted
							$customer_exists = false;

						}

					// No customer found
					} catch ( Exception $e ) {


						$customer_exists = false;

					}

				}

				// Customer with a discount
				if ( ! empty( $this->discount_code ) ) {

					if( $customer_exists ) {

						$customer->card   = $_POST['stripeToken'];
						$customer->coupon = $this->discount_code;
						$customer->save();

						// Update the customer's subscription in Stripe
						$customer_response = $customer->updateSubscription( array( 'plan' => $plan_id ) );

					} else {

						$customer = Stripe_Customer::create( array(
								'card' 			=> $_POST['stripeToken'],
								'plan' 			=> $plan_id,
								'email' 		=> $this->email,
								'description' 	=> 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name,
								'coupon' 		=> $_POST['rcp_discount']
							)
						);

					}

				// Customer without a discount
				} else {

					if( $customer_exists ) {

						$customer->card   = $_POST['stripeToken'];
						$customer->save();

						// Update the customer's subscription in Stripe
						$customer_response = $customer->updateSubscription( array( 'plan' => $plan_id ) );

					} else {

						$customer = Stripe_Customer::create( array(
								'card' 			=> $_POST['stripeToken'],
								'plan' 			=> $plan_id,
								'email' 		=> $this->email,
								'description' 	=> 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name
							)
						);

					}

				}

				if ( ! empty( $this->fee ) ) {

					if( $this->fee > 0 ) {
						$description = sprintf( __( 'Signup Fee for %s', 'rcp_stripe' ), $this->subscription_name );
					} else {
						$description = sprintf( __( 'Signup Discount for %s', 'rcp_stripe' ), $this->subscription_name );
					}

					Stripe_InvoiceItem::create( array(
							'customer'    => $customer->id,
							'amount'      => $this->fee * 100,
							'currency'    => strtolower( $this->currency ),
							'description' => $description
						)
					);

					// Create the invoice containing taxes / discounts / fees
					$invoice = Stripe_Invoice::create( array(
						'customer' => $customer->id, // the customer to apply the fee to
					) );
					$invoice->pay();

				}

				$member->set_payment_profile_id( $customer->id );

				// subscription payments are recorded via webhook

				$paid = true;

			} catch ( Stripe_CardError $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

				exit;

			} catch (Stripe_InvalidRequestError $e) {

				// Invalid parameters were supplied to Stripe's API
				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_AuthenticationError $e) {

				// Authentication with Stripe's API failed
				// (maybe you changed API keys recently)

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_ApiConnectionError $e) {

				// Network communication with Stripe failed

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_Error $e) {

				// Display a very generic error to the user

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Exception $e) {

				// Something else happened, completely unrelated to Stripe

				$error = "<p>An unidentified error occurred.</p>";
				$error .= print_r( $e, true );

				wp_die( $error );

			}

		} else {

			// process a one time payment signup

			try {

				$charge = Stripe_Charge::create( array(
						'amount' 		=> $this->amount * 100, // amount in cents
						'currency' 		=> strtolower( $this->currency ),
						'card' 			=> $_POST['stripeToken'],
						'description' 	=> 'User ID: ' . $this->user_id . ' - User Email: ' . $this->email . ' Subscription: ' . $this->subscription_name
					)
				);

				$payment_data = array(
					'date'              => date( 'Y-m-d g:i:s', time() ),
					'subscription'      => $this->subscription_name,
					'payment_type' 		=> 'Credit Card One Time',
					'subscription_key' 	=> $this->subscription_key,
					'amount' 			=> $this->amount,
					'user_id' 			=> $this->user_id,
					'transaction_id'    => $charge->id
				);

				$rcp_payments = new RCP_Payments();
				$rcp_payments->insert( $payment_data );

				$paid = true;

			} catch ( Stripe_CardError $e ) {

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

				exit;

			} catch (Stripe_InvalidRequestError $e) {

				// Invalid parameters were supplied to Stripe's API
				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_AuthenticationError $e) {

				// Authentication with Stripe's API failed
				// (maybe you changed API keys recently)

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_ApiConnectionError $e) {

				// Network communication with Stripe failed

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_Error $e) {

				// Display a very generic error to the user

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = "<h4>An error occurred</h4>";
				if( isset( $err['code'] ) ) {
					$error .= "<p>Error code: " . $err['code'] ."</p>";
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Exception $e) {

				// Something else happened, completely unrelated to Stripe

				$error = "<p>An unidentified error occurred.</p>";
				$error .= print_r( $e, true );

				wp_die( $error );

			}
		}

		if ( $paid ) {

			// set this user to active
			$member->renew();

			if ( ! is_user_logged_in() ) {

				// log the new user in
				rcp_login_user_in( $this->user_id, $this->user_name, $_POST['rcp_user_pass'] );

			}

			do_action( 'rcp_stripe_signup', $this->user_id, $this );

		} else {

			wp_die( __( 'An error occurred, please contact the site administrator: ', 'rcp_stripe' ) . get_bloginfo( 'admin_email' ), __( 'Error', 'rcp' ), array( '401' ) );

		}

		// redirect to the success page, or error page if something went wrong
		wp_redirect( $this->return_url ); exit;

	}

	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'stripe' ) {
			return;
		}

		// Ensure listener URL is not cached by W3TC
		define( 'DONOTCACHEPAGE', true );

		Stripe::setApiKey( $this->secret_key );

		// retrieve the request's body and parse it as JSON
		$body          = @file_get_contents( 'php://input' );
		$event_json_id = json_decode( $body );

		// for extra security, retrieve from the Stripe API
		if ( isset( $event_json_id->id ) ) {

			$rcp_payments = new RCP_Payments();

			$event_id = $event_json_id->id;

			try {

				$event = Stripe_Event::retrieve( $event_id );

				$invoice = $event->data->object;

				// retrieve the customer who made this payment (only for subscriptions)
				$user   = rcp_stripe_get_user_id( $invoice->customer );
				$member = new RCP_Member( $user );
				
				// check to confirm this is a stripe subscriber
				if ( $member ) {

					// successful payment
					if ( $event->type == 'charge.succeeded' ) {

						if( ! $member->get_subscription_id() )
							return;

						$payment_data = array(
							'date'              => date( 'Y-m-d g:i:s', $event->created ),
							'subscription'      => $member->get_subscription_name(),
							'payment_type' 		=> 'Credit Card',
							'subscription_key' 	=> $member->get_subscription_key(),
							'amount' 			=> $invoice->amount / 100,
							'user_id' 			=> $member->ID,
							'transaction_id'    => $invoice->id
						);

						if( ! rcp_check_for_existing_payment( $payment_data['payment_type'], $payment_data['date'], $payment_data['subscription_key'] ) ) {

							// record this payment if it hasn't been recorded yet
							$rcp_payments->insert( $payment_data );

							$member->renew();

							do_action( 'rcp_stripe_charge_succeeded', $user, $payment_data );

						}

					}

					// failed payment
					if ( $event->type == 'charge.failed' ) {

						// send email alerting the user of the failed payment
						rcp_email_failed_payment_notice( $invoice );

						do_action( 'rcp_stripe_charge_failed', $invoice );

					}

					// Cancelled / failed subscription
					if( $event->type == 'customer.subscription.deleted' ) {

						$member->set_status( 'cancelled' );

					}

					do_action( 'rcp_stripe_' . $event->type, $invoice );

				}


			} catch ( Exception $e ) {
				// something failed
			}
		}
		exit;

	}

	public function fields() {

		ob_start();
?>
		<script type="text/javascript">
			// this identifies your website in the createToken call below
			Stripe.setPublishableKey('<?php echo $this->publishable_key; ?>');

			function stripeResponseHandler(status, response) {

				if (response.error) {
					// re-enable the submit button
					jQuery('#rcp_registration_form #rcp_submit').attr("disabled", false);

					jQuery('#rcp_ajax_loading').hide();

					// show the errors on the form
					jQuery(".payment-errors").html(response.error.message);

				} else {
					var form$ = jQuery("#rcp_registration_form");
					// token contains id, last4, and card type
					var token = response['id'];
					// insert the token into the form so it gets submitted to the server
					form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");

					// and submit
					form$.get(0).submit();

				}
			}

			jQuery(document).ready(function($) {

				$("#rcp_submit").on('click', function(event) {
					// get the subscription price

					if( $('.rcp_level:checked').length ) {
						var price = $('.rcp_level:checked').closest('li').find('span.rcp_price').attr('rel') * 100;
					} else {
						var price = $('.rcp_level').attr('rel') * 100;
					}

					if( ( $('select#rcp_gateway option:selected').val() == 'stripe' || $('input[name=rcp_gateway]').val() == 'stripe') && price > 0) {
						if( ! $('.rcp_gateway_fields').is(':visible') ) {
							return true;
						}

						event.preventDefault();

						// disable the submit button to prevent repeated clicks
						$('#rcp_registration_form #rcp_submit').attr("disabled", "disabled");
						$('#rcp_ajax_loading').show();

						// createToken returns immediately - the supplied callback submits the form if there are no errors
						Stripe.createToken({
							number: $('.card-number').val(),
							name: $('.card-name').val(),
							cvc: $('.card-cvc').val(),
							exp_month: $('.card-expiry-month').val(),
							exp_year: $('.card-expiry-year').val(),
							address_zip: $('.card-zip').val()
						}, stripeResponseHandler);

						return false;
					}
				});
			});
		</script>
<?php
		rcp_get_template_part( 'card-form' );
		return ob_get_clean();
	}

	public function scripts() {
		wp_enqueue_script( 'stripe', 'https://js.stripe.com/v2/', array( 'jquery' ) );
	}

	private function create_plan( $plan_id = '' ) {

		// get all subscription level info for this plan
		$plan           = rcp_get_subscription_details_by_name( $plan_name );
		$price          = $plan->price * 100;
		$interval       = $plan->duration_unit;
		$interval_count = $plan->duration;
		$name           = $plan->name;
		$plan_id        = strtolower( str_replace( ' ', '', $plan_name ) );
		$currency       = strtolower( $rcp_options['currency'] );

		Stripe::setApiKey( $this->secret_key );

		try {

			Stripe_Plan::create( array(
				"amount"         => $price,
				"interval"       => $interval,
				"interval_count" => $interval_count,
				"name"           => $name,
				"currency"       => $currency,
				"id"             => $plan_id
			) );

			// plann successfully created
			return true;

		} catch ( Exception $e ) {
			// there was a problem
			return false;
		}

	}

	private function plan_exists( $plan_id = '' ) {

		$plan_id = strtolower( str_replace( ' ', '', $plan_id ) );

		Stripe::setApiKey( $this->secret_key );

		try {
			$plan = Stripe_Plan::retrieve( $plan_id );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

}


if( ! function_exists( 'rcp_stripe_add_discount' ) ) {
	function rcp_stripe_add_discount() {
		global $rcp_options;

		if ( isset( $rcp_options['stripe_test_mode'] ) ) {
			$secret_key = trim( $rcp_options['stripe_test_secret'] );
		} else {
			$secret_key = trim( $rcp_options['stripe_live_secret'] );
		}

		Stripe::setApiKey( $secret_key );

		try {

			if ( $_POST['unit'] == '%' ) {
				Stripe_Coupon::create( array(
						"percent_off" => sanitize_text_field( $_POST['amount'] ),
						"duration"    => "forever",
						"id"          => sanitize_text_field( $_POST['code'] ),
						"currency"   => strtolower( $rcp_options['currency'] )
					)
				);
			} else {
				Stripe_Coupon::create( array(
						"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
						"duration"   => "forever",
						"id"         => sanitize_text_field( $_POST['code'] ),
						"currency"   => strtolower( $rcp_options['currency'] )
					)
				);
			}

		} catch ( Exception $e ) {
			wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
		}

	}
	add_action( 'rcp_pre_add_discount', 'rcp_stripe_add_discount' );
}

if( ! function_exists( 'rcp_stripe_edit_discount' ) ) {
	function rcp_stripe_edit_discount() {
		global $rcp_options;

		if ( isset( $rcp_options['sandbox'] ) ) {
			$secret_key = trim( $rcp_options['stripe_test_secret'] );
		} else {
			$secret_key = trim( $rcp_options['stripe_live_secret'] );
		}

		Stripe::setApiKey( $secret_key );

		if ( ! rcp_stripe_coupon_exists( $_POST['code'] ) ) {

			try {

				if ( $_POST['unit'] == '%' ) {
					Stripe_Coupon::create( array(
							"percent_off" => sanitize_text_field( $_POST['amount'] ),
							"duration"    => "forever",
							"id"          => sanitize_text_field( $_POST['code'] ),
							"currency"    => strtolower( $rcp_options['currency'] )
						)
					);
				} else {
					Stripe_Coupon::create( array(
							"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
							"duration"   => "forever",
							"id"         => sanitize_text_field( $_POST['code'] ),
							"currency"   => strtolower( $rcp_options['currency'] )
						)
					);
				}

			} catch ( Exception $e ) {
				wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
			}

		} else {

			// first delete the discount in Stripe
			try {
				$cpn = Stripe_Coupon::retrieve( $_POST['code'] );
				$cpn->delete();
			} catch ( Exception $e ) {
				wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
			}

			// now add a new one. This is a fake "update"
			try {

				if ( $_POST['unit'] == '%' ) {
					Stripe_Coupon::create( array(
							"percent_off" => sanitize_text_field( $_POST['amount'] ),
							"duration"    => "forever",
							"id"          => sanitize_text_field( $_POST['code'] ),
							"currency"    => strtolower( $rcp_options['currency'] )
						)
					);
				} else {
					Stripe_Coupon::create( array(
							"amount_off" => sanitize_text_field( $_POST['amount'] ) * 100,
							"duration"   => "forever",
							"id"         => sanitize_text_field( $_POST['code'] ),
							"currency"   => strtolower( $rcp_options['currency'] )
						)
					);
				}

			} catch ( Exception $e ) {
				wp_die( '<pre>' . $e . '</pre>', __( 'Error', 'rcp_stripe' ) );
			}
		}
	}
	add_action( 'rcp_edit_discount', 'rcp_stripe_edit_discount' );
}

if( ! function_exists( 'rcp_stripe_coupon_exists' ) ) {
	function rcp_stripe_coupon_exists( $code ) {
		global $rcp_options;

		if ( isset( $rcp_options['stripe_test_mode'] ) ) {
			$secret_key = trim( $rcp_options['stripe_test_secret'] );
		} else {
			$secret_key = trim( $rcp_options['stripe_live_secret'] );
		}

		Stripe::setApiKey( $secret_key );
		try {
			Stripe_Coupon::retrieve( $code );
			$exists = true;
		} catch ( Exception $e ) {
			$exists = false;
		}
		return $exists;
	}
}