<?php
/**
 * Displays the system info report
 */
function rcp_tools_system_info_report() {

	global $rcp_options, $wpdb;

	// Get theme info
	$theme_data = wp_get_theme();
	$theme      = $theme_data->Name . ' ' . $theme_data->Version;

	$return  = '### Begin System Info ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if( get_option( 'show_on_front' ) === 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	// RCP Config
	$license_key                = $rcp_options['license_key'];
	$auto_renew                 = $rcp_options['auto_renew'];
	$auto_renew_options         = array( 1 => 'Always auto renew', 2 => 'Never auto renew', 3 => 'Let customer choose whether to auto renew' );
	$currency                   = $rcp_options['currency'];
	$currency_position          = $rcp_options['currency_position'];
	$sandbox                    = $rcp_options['sandbox'];

	$return .= "\n" . '-- RCP Configuration' . "\n\n";
	$return .= 'Version:                          ' . RCP_PLUGIN_VERSION . "\n";
	$return .= 'License Key:                      ' . ( ! empty( $license_key ) ? $license_key . "\n" : "Not set\n" );
	$return .= 'Auto Renew:                       ' . ( ! empty( $auto_renew ) && array_key_exists( $auto_renew, $auto_renew_options ) ? $auto_renew_options[$auto_renew] . "\n" : "Invalid Configuration\n" );
	$return .= 'Currency:                         ' . ( ! empty( $currency ) ? $currency . "\n" : "Invalid Configuration\n" );
	$return .= 'Currency Position:                ' . ( ! empty( $currency_position ) ? $currency_position . "\n" : "Invalid Configuration\n" );
	$return .= 'Sandbox Mode:                     ' . ( ! empty( $sandbox ) ? "True" . "\n" : "False\n" );


	// RCP pages
	$registration_page = $rcp_options['registration_page'];
	$success_page      = $rcp_options['redirect'];
	$account_page      = $rcp_options['account_page'];
	$edit_profile_page = $rcp_options['edit_profile'];
	$update_card_page  = $rcp_options['update_card'];

	$return .= "\n" . '-- RCP Page Configuration' . "\n\n";
	$return .= 'Registration Page:                ' . ( ! empty( $registration_page ) ? get_permalink( $registration_page ) . "\n" : "Unset\n" );
	$return .= 'Success Page:                     ' . ( ! empty( $success_page ) ? get_permalink( $success_page ) . "\n" : "Unset\n" );
	$return .= 'Account Page:                     ' . ( ! empty( $account_page ) ? get_permalink( $account_page ) . "\n" : "Unset\n" );
	$return .= 'Edit Profile Page:                ' . ( ! empty( $edit_profile_page ) ? get_permalink( $edit_profile_page ) . "\n" : "Unset\n" );
	$return .= 'Update Billing Card Page:         ' . ( ! empty( $update_card_page ) ? get_permalink( $update_card_page ) . "\n" : "Unset\n" );

	// RCP gateways
	$return .= "\n" . '-- RCP Gateway Configuration' . "\n\n";

	$active_gateways = rcp_get_enabled_payment_gateways();

	if( $active_gateways ) {
		$gateways = array();
		foreach( $active_gateways as $key => $label ) {
			$gateways[] = $label . ' (' . $key . ')';
		}

		$return .= 'Enabled Gateways:         ' . implode( ', ', $gateways ) . "\n";
	} else {
		$return .= 'Enabled Gateways:         None' . "\n";
	}

	// RCP Misc Settings
	$hide_premium_posts         = ( ! empty( $rcp_options['hide_premium'] ) ? "True" : "False" );
	$redirect_from_premium      = $rcp_options['redirect_from_premium'];
	$redirect_default_login_url = ( ! empty( $rcp_options['hijack_login_url'] ) ? "True" : "False" );
	$redirect_login_page        = $rcp_options['login_redirect'];
	$prevent_account_sharing    = ( ! empty( $rcp_options['no_login_sharing'] ) ? "True" : "False" );
	$email_ipn_reports          = ( ! empty( $rcp_options['email_ipn_reports'] ) ? "True" : "False" );
	$disable_form_css           = ( ! empty( $rcp_options['disable_css'] ) ? "True" : "False" );
	$enable_recaptcha           = ( ! empty( $rcp_options['enable_recaptcha'] ) ? "True" : "False" );
	$recaptcha_public_key       = $rcp_options['recaptcha_public_key'];
	$recaptcha_private_key      = $rcp_options['recaptcha_private_key'];

	$return .= "\n" . '-- RCP Misc Settings' . "\n\n";
	$return .= 'Hide Premium Posts:               ' . $hide_premium_posts . "\n";
	$return .= 'Redirect Page:                    ' . ( ! empty( $redirect_from_premium ) ? get_permalink( $redirect_from_premium ) . "\n" : "Unset\n" );
	$return .= 'Redirect Default Login URL        ' . $redirect_default_login_url . "\n";
	$return .= 'Login Page:                       ' . ( ! empty( $redirect_login_page ) ? get_permalink( $redirect_login_page ) . "\n" : "Unset\n" );
	$return .= 'Prevent Account Sharing:          ' . $prevent_account_sharing . "\n";
	$return .= 'Email IPN Reports:                ' . $email_ipn_reports . "\n";
	$return .= 'Disable Form CSS:                 ' . $disable_form_css . "\n";
	$return .= 'Enable reCaptcha:                 ' . $enable_recaptcha . "\n";
	$return .= 'reCaptcha Site Key:               ' . ( ! empty( $recaptcha_public_key ) ? "Set\n" : "Unset\n" );
	$return .= 'reCaptcha Secret Key:             ' . ( ! empty( $recaptcha_secret_key ) ? "Set\n" : "Unset\n" );

	// RCP Templates
	$dir = get_stylesheet_directory() . '/rcp/';

	if( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- RCP Template Overrides' . "\n\n";

		foreach( glob( $dir . '/*' ) as $file ) {
			$return .= 'Filename:                 ' . basename( $file ) . "\n";
		}
	}

	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if( count( $muplugins > 0 ) ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach( $plugins as $plugin_path => $plugin ) {
		if( !in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach( $plugins as $plugin_path => $plugin ) {
		if( in_array( $plugin_path, $active_plugins ) )
			continue;

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	if( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if( !array_key_exists( $plugin_base, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	// PHP configuration
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return .= "\n" . '### End System Info ###';

	return $return;
}