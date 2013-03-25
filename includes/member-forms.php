<?php

// login form fields
function rcp_login_form_fields( $args = array() ) {

	global $post;

	$action = rcp_get_current_url();

	// parse the arguments passed
	$defaults = array (
 		'redirect' => $action,
 		'class' => 'rcp_form'
	);
	$args = wp_parse_args( $args, $defaults );
	// setup each argument in its own variable
	extract( $args, EXTR_SKIP );

	ob_start();

		do_action( 'rcp_before_login_form' );

		if( !is_user_logged_in() ) {

			// show any error messages after form submission
			rcp_show_error_messages( 'login' ); ?>

			<form id="rcp_login_form"  class="<?php echo $class; ?>" method="POST" action="<?php echo esc_url( $action ); ?>">
				<fieldset class="rcp_login_data">
					<p>
						<label for="rcp_user_Login"><?php _e( 'Username', 'rcp' ); ?></label>
						<input name="rcp_user_login" id="rcp_user_login" class="required" type="text"/>
					</p>
					<p>
						<label for="rcp_user_pass"><?php _e( 'Password', 'rcp' ); ?></label>
						<input name="rcp_user_pass" id="rcp_user_pass" class="required" type="password"/>
					</p>
					<p class="rcp_lost_password"><a href="<?php echo esc_url( wp_lostpassword_url( $action ) ); ?>"><?php _e( 'Lost your password?', 'rcp' ); ?></a></p>
					<p>
						<input type="hidden" name="rcp_action" value="login"/>
						<input type="hidden" name="rcp_redirect" value="<?php echo esc_url( $redirect ); ?>"/>
						<input type="hidden" name="rcp_login_nonce" value="<?php echo wp_create_nonce( 'rcp-login-nonce' ); ?>"/>
						<input id="rcp_login_submit" type="submit" value="Login"/>
					</p>
				</fieldset>
			</form>
			<?php
		} else {
			echo '<div class="rcp_logged_in">' . __( 'You are logged in.', 'rcp' ) . ' <a href="' . wp_logout_url( home_url() ) . '">' . __( 'Logout', 'rcp' ) . '</a></div>';
		}

		do_action( 'rcp_after_login_form' );

	return ob_get_clean();
}

// registration form fields
function rcp_registration_form_fields( $args = array() ) {
	ob_start();
	do_action( 'rcp_before_register_form' );
	rcp_get_template_part( 'register' );
	do_action( 'rcp_after_register_form' );
	return ob_get_clean();
}

function rcp_change_password_form( $args = array() ) {
	global $post;

	$redirect = rcp_get_current_url();

	// parse the arguments passed
	$defaults = array (
 		'redirect' => $redirect,
 		'class' => 'rcp_form'
	);
	$args = wp_parse_args( $args, $defaults );
	// setup each argument in its own variable
	extract( $args, EXTR_SKIP );

	ob_start();

		do_action( 'rcp_before_password_form' );

		// show any error messages after form submission
		rcp_show_error_messages( 'password' ); ?>

		<?php if( isset( $_GET['password-reset']) && $_GET['password-reset'] == 'true') { ?>
			<div class="rcp_message success">
				<span><?php _e( 'Password changed successfully', 'rcp' ); ?></span>
			</div>
		<?php } ?>
		<form id="rcp_password_form"  class="<?php echo esc_attr( $class ); ?>" method="POST" action="<?php echo esc_url( $redirect ); ?>">
			<fieldset class="rcp_change_password_fieldset">
				<p>
					<label for="rcp_user_pass"><?php echo apply_filters ( 'rcp_registration_new_password_label', __( 'New Password', 'rcp' ) ); ?></label>
					<input name="rcp_user_pass" id="rcp_user_pass" class="required" type="password"/>
				</p>
				<p>
					<label for="rcp_user_pass_confirm"><?php echo apply_filters ( 'rcp_registration_confirm_password_label', __( 'Password Confirm', 'rcp' ) ); ?></label>
					<input name="rcp_user_pass_confirm" id="rcp_user_pass_confirm" class="required" type="password"/>
				</p>
				<p>
					<input type="hidden" name="rcp_action" value="reset-password"/>
					<input type="hidden" name="rcp_redirect" value="<?php echo esc_url( $redirect ); ?>"/>
					<input type="hidden" name="rcp_password_nonce" value="<?php echo wp_create_nonce('rcp-password-nonce' ); ?>"/>
					<input id="rcp_password_submit" type="submit" value="<?php echo apply_filters ( 'rcp_registration_change_password_button', __( 'Change Password', 'rcp' ) ); ?>"/>
				</p>
			</fieldset>
		</form>
		<?php
		do_action( 'rcp_after_password_form' );
	return ob_get_clean();
}

function rcp_add_auto_renew( $levels ) {
	global $rcp_options;
	if( $levels && !isset( $rcp_options['disable_auto_renew'] ) ) : ?>
	<p id="rcp_auto_renew_wrap">
		<input name="rcp_auto_renew" id="rcp_auto_renew" type="checkbox" checked="checked"/>
		<label for="rcp_auto_renew"><?php echo apply_filters ( 'rcp_registration_auto_renew', __( 'Auto Renew', 'rcp' ) ); ?></label>
	</p>
	<?php endif;
}
add_action( 'rcp_before_registration_submit_field', 'rcp_add_auto_renew' );