<?php

/**
 * Register default payment gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_payment_gateways() {
	$gateways = new RCP_Payment_Gateways;
	return $gateways->available_gateways;
}

/**
 * Return list of active gateways
 *
 * @access      private
 * @return      array
*/
function rcp_get_enabled_payment_gateways() {

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) ) {

			$gateways->enabled_gateways[ $key ] = $gateway['label'];

		}

	}

	return $gateways->enabled_gateways;
}


/**
 * Send payment / subscription data to gateway
 *
 * @access      private
 * @return      array
*/
function rcp_send_to_gateway( $gateway, $subscription_data ) {

	if( has_action( 'rcp_gateway_' . $gateway ) ) {

		do_action( 'rcp_gateway_' . $gateway, $subscription_data );
	
	} else {
	
		$gateways = new RCP_Payment_Gateways;
		$gateway  = $gateways->get_gateway( $gateway );
		$gateway  = new $gateway['class']( $subscription_data );

		$gateway->process_signup();

	}

}

/**
 * Determines if a gateway supports recurring payments
 *
 * @access      public
 * @since      2.1
 * @return      bool
*/
function rcp_gateway_supports( $gateway = 'paypal', $item = 'recurring' ) {

	$ret      = true;
	$gateways = new RCP_Payment_Gateways;
	$gateway  = $gateways->get_gateway( $gateway );
	
	if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

		$gateway = new $gateway['class'];
		$ret     = $gateway->supports( 'recurring' );

	}

	return $ret;

}

/**
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_process_gateway_webooks() {

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->available_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

			$gateway = new $gateway['class'];
			$gateway->process_webhooks();

		}

	}

}
add_action( 'init', 'rcp_process_gateway_webooks', -99999 );

/**
 * Load webhook processor for all gateways
 *
 * @access      public
 * @since       2.1
 * @return      void
*/
function rcp_load_gateway_scripts() {

	global $rcp_options;

	if( ! is_page( $rcp_options['registration_page'] ) && ! rcp_is_registration_page() ) {
		return;
	}

	$gateways = new RCP_Payment_Gateways;

	foreach( $gateways->enabled_gateways  as $key => $gateway ) {

		if( is_array( $gateway ) && isset( $gateway['class'] ) ) {

			$gateway = new $gateway['class'];
			$gateway->scripts();

		}

	}

}
add_action( 'wp_enqueue_scripts', 'rcp_load_gateway_scripts', 100 );

/**
 * Determine if PayPal API access is enabled
 *
 * @access      public
 * @since       2.1
 */
function rcp_has_paypal_api_access() {
	global $rcp_options;

	$ret    = false;
	$prefix = 'live_';

	if( isset( $rcp_options['sandbox'] ) ) {
		$prefix = 'test_';
	}

	$username  = $prefix . 'paypal_api_username';
	$password  = $prefix . 'paypal_api_password';
	$signature = $prefix . 'paypal_api_signature';

	if( ! empty( $rcp_options[ $username ] ) && ! empty( $rcp_options[ $password ] ) && ! empty( $rcp_options[ $signature ] ) ) {

		$ret = true;

	}

	return $ret;
}

if( ! function_exists( 'rcp_stripe_add_discount' ) ) {
	function rcp_stripe_add_discount() {
		
		if( ! is_admin() ) {
			return;
		}

		global $rcp_options;

		if( ! class_exists( 'Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/Stripe.php';
		}

		if ( isset( $rcp_options['sandbox'] ) ) {
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
	add_action( 'rcp_pre_add_discount', 'rcp_stripe_add_discount' );
}

if( ! function_exists( 'rcp_stripe_edit_discount' ) ) {
	function rcp_stripe_edit_discount() {

		if( ! is_admin() ) {
			return;
		}

		global $rcp_options;

		if( ! class_exists( 'Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/Stripe.php';
		}

		if ( isset( $rcp_options['sandbox'] ) ) {
			$secret_key = trim( $rcp_options['stripe_test_secret'] );
		} else {
			$secret_key = trim( $rcp_options['stripe_live_secret'] );
		}

		Stripe::setApiKey( $secret_key );

		if ( ! rcp_stripe_coupon_exists( $_POST['rcp_discount'] ) ) {

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
	}
	add_action( 'rcp_edit_discount', 'rcp_stripe_edit_discount' );
}

if( ! function_exists( 'rcp_stripe_coupon_exists' ) ) {
	function rcp_stripe_coupon_exists( $code ) {
		global $rcp_options;

		if( ! class_exists( 'Stripe' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/stripe/Stripe.php';
		}

		if ( isset( $rcp_options['sandbox'] ) ) {
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