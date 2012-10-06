<?php

function rcp_validate_captcha() {
	global $rcp_options;
	/* validate recaptcha, if enabled */
	$privatekey = trim( $rcp_options['recaptcha_private_key'] );
	$resp = recaptcha_check_answer(
		$privatekey,
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]
	);
	if ( !$resp->is_valid ) {
		// recaptcha is incorrect
		rcp_errors()->add( 'invalid_recaptcha', __( 'The words/numbers you entered did not match the reCaptcha', 'rcp' ) );
	}
}
add_action( 'rcp_form_errors', 'rcp_validate_captcha' );