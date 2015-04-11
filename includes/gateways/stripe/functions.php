<?php

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

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

				exit;

		} catch (Stripe_InvalidRequestError $e) {

			// Invalid parameters were supplied to Stripe's API
			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error );

		} catch (Stripe_AuthenticationError $e) {

			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error );

		} catch (Stripe_ApiConnectionError $e) {

			// Network communication with Stripe failed

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
			}
			$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
			$error .= "<p>Message: " . $err['message'] . "</p>";

			wp_die( $error );

		} catch (Stripe_Error $e) {

			// Display a very generic error to the user

			$body = $e->getJsonBody();
			$err  = $body['error'];

			$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
			if( isset( $err['code'] ) ) {
				$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
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

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_AuthenticationError $e) {

				// Authentication with Stripe's API failed
				// (maybe you changed API keys recently)

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_ApiConnectionError $e) {

				// Network communication with Stripe failed

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
				}
				$error .= "<p>Status: " . $e->getHttpStatus() ."</p>";
				$error .= "<p>Message: " . $err['message'] . "</p>";

				wp_die( $error );

			} catch (Stripe_Error $e) {

				// Display a very generic error to the user

				$body = $e->getJsonBody();
				$err  = $body['error'];

				$error = '<h4>' . __( 'An error occurred', 'rcp' ) . '</h4>';
				if( isset( $err['code'] ) ) {
					$error .= '<p>' . sprintf( __( 'Error code: %s', 'rcp' ), $err['code'] ) . '</p>';
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