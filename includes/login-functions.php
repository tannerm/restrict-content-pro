<?php
/**
 * Login Functions
 *
 * Processes the login forms and also the login process during registration
 *
 * @package     Restrict Content Pro
 * @subpackage  Login Functions
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieves the login URl with an optional redirect
 *
 * @access      public
 * @since       2.1
 */
function rcp_get_login_url( $redirect = '' ) {

	global $rcp_options;

	if( isset( $rcp_options['hijack_login_url'] ) && ! empty( $rcp_options['login_redirect'] ) ) {

		$url = add_query_arg( 'redirect', $redirect, get_permalink( absint( $rcp_options['login_redirect'] ) ) );

	} else {

		$url = wp_login_url( $redirect );

	}

	return apply_filters( 'rcp_login_url', $url, $redirect );

}

/**
 * Log a user in
 *
 * @access      public
 * @since       1.0
 */
function rcp_login_user_in( $user_id, $user_login, $remember = false ) {
	$user = get_userdata( $user_id );
	if( ! $user )
		return;
	wp_set_auth_cookie( $user_id, $remember );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, $user );
}


/**
 *Process the login form
 *
 * @access      public
 * @since       1.0
 */
function rcp_process_login_form() {

	if( ! isset( $_POST['rcp_action'] ) || 'login' != $_POST['rcp_action'] ) {
		return;
	}

	if( ! isset( $_POST['rcp_login_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_login_nonce'], 'rcp-login-nonce' ) ) {
		return;
	}

	// this returns the user ID and other info from the user name
	$user = get_user_by( 'login', $_POST['rcp_user_login'] );

	do_action( 'rcp_before_form_errors', $_POST );

	if( !$user ) {
		// if the user name doesn't exist
		rcp_errors()->add( 'empty_username', __( 'Invalid username', 'rcp' ), 'login' );
	}

	if( !isset( $_POST['rcp_user_pass'] ) || $_POST['rcp_user_pass'] == '') {
		// if no password was entered
		rcp_errors()->add( 'empty_password', __( 'Please enter a password', 'rcp' ), 'login' );
	}

	if( $user ) {
		// check the user's login with their password
		if( !wp_check_password( $_POST['rcp_user_pass'], $user->user_pass, $user->ID ) ) {
			// if the password is incorrect for the specified user
			rcp_errors()->add( 'empty_password', __( 'Incorrect password', 'rcp' ), 'login' );
		}
	}

	if( function_exists( 'is_limit_login_ok' ) && ! is_limit_login_ok() ) {

		rcp_errors()->add( 'limit_login_failed', limit_login_error_msg(), 'login' );

	}

	do_action( 'rcp_login_form_errors', $_POST );

	// retrieve all error messages
	$errors = rcp_errors()->get_error_messages();

	// only log the user in if there are no errors
	if( empty( $errors ) ) {

		$remember = isset( $_POST['rcp_user_remember'] );

		$redirect = ! empty( $_POST['rcp_redirect'] ) ? $_POST['rcp_redirect'] : home_url();

		rcp_login_user_in( $user->ID, $_POST['rcp_user_login'], $remember );

		// redirect the user back to the page they were previously on
		wp_redirect( $redirect ); exit;

	} else {

		if( function_exists( 'limit_login_failed' ) ) {
			limit_login_failed( $_POST['rcp_user_login'] );
		}

	}
}
add_action('init', 'rcp_process_login_form');
/**
 * Process the lost password form
 *
 * @access      public
 * @since       1.0
 */
function rcp_process_lostpassword_form() {
	if( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['rcp_action'] ) || 'lostpassword' != $_POST['rcp_action'] ) {
		return;
	}

	if( ! isset( $_POST['rcp_lostpassword_nonce'] ) || ! wp_verify_nonce( $_POST['rcp_lostpassword_nonce'], 'rcp-lostpassword-nonce' ) ) {
		return;
	}

	rcp_retrieve_password();
}
add_action('init', 'rcp_process_lostpassword_form');

/**
 * Send password reset email to user. Adapted from wp-login.php
 *
 * @access      public
 * @since       1.0
 */
function rcp_retrieve_password() {
	global $wpdb, $wp_hasher;

	if ( empty( $_POST['rcp_user_login'] ) ) {
		rcp_errors()->add( 'empty_username', __( 'Enter a username or e-mail address.', 'rcp' ), 'lostpassword' );
	} elseif ( strpos( $_POST['rcp_user_login'], '@' ) ) {
		$user_data = get_user_by( 'email', trim( $_POST['rcp_user_login'] ) );
		if ( empty( $user_data ) )
			rcp_errors()->add( 'invalid_email', __( 'There is no user registered with that email address.', 'rcp' ), 'lostpassword' );
	} else {
		$login = trim($_POST['rcp_user_login']);
		$user_data = get_user_by('login', $login);
	}

	if ( rcp_errors()->get_error_code() )
		return rcp_errors();

	if ( !$user_data ) {
		rcp_errors()->add('invalidcombo', __('Invalid username or e-mail.', 'rcp' ), 'lostpassword');
		return rcp_errors();
	}

	// Redefining user_login ensures we return the right case in the email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;

	$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

	if ( ! $allow ) {
		rcp_errors()->add( 'no_password_reset', __( 'Password reset is not allowed for this user', 'rcp' ), 'lostpassword' );
		return rcp_errors();
	} elseif ( is_wp_error( $allow ) ) {
		return $allow;
	}

	// Generate something random for a password reset key.
	$key = wp_generate_password( 20, false );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );

	$message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
	$message .= network_home_url( '/' ) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
	$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
	$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

	if ( is_multisite() )
		$blogname = $GLOBALS['current_site']->site_name;
	else
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option we want to reverse this for the plain text arena of emails.
		 */
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$title = sprintf( __('[%s] Password Reset'), $blogname );

	$title = apply_filters( 'retrieve_password_title', $title );

	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

	if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
		wp_die( __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );

	return true;
}