<?php
/*
Plugin Name: Restrict Content Pro
Plugin URL: http://pippinsplugins.com/restrict-content-pro-premium-content-plugin
Description: Setup a complete subscription system for your WordPress site and deliver premium content to your subscribers. Unlimited subscription packages, membership management, discount codes, registration / login forms, and more.
Version: 1.1.9
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Contributors: mordauk
*/


/*******************************************
* global variables
*******************************************/
global $wpdb;

// load the plugin options
$rcp_options = get_option( 'rcp_settings' );

// the plugin base directory
global $rcp_base_dir;
$rcp_base_dir = dirname(__FILE__);

global $rcp_db_name;
$rcp_db_name = $wpdb->prefix . 'restrict_content_pro';

global $rcp_db_version;
$rcp_db_version = 1.2;

global $rcp_discounts_db_name;
$rcp_discounts_db_name = $wpdb->prefix . 'rcp_discounts';

global $rcp_discounts_db_version;
$rcp_discounts_db_version = 1.1;

global $rcp_payments_db_name;
$rcp_payments_db_name = $wpdb->prefix . 'rcp_payments';

global $rcp_payments_db_version;
$rcp_payments_db_version = 1.1;

/* settings page globals */
global $rcp_members_page;
global $rcp_subscriptions_page;
global $rcp_discounts_page;
global $rcp_payments_page;
global $rcp_settings_page;
global $rcp_export_page;

if(!defined('RCP_PLUGIN_DIR')) {
	define('RCP_PLUGIN_DIR', plugin_dir_url( __FILE__ ));
}
if(!defined('RCP_PLUGIN_FILE')) {
	define('RCP_PLUGIN_FILE', __FILE__ );
}

/*******************************************
* plugin text domain for translations
*******************************************/

load_plugin_textdomain( 'rcp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/*
error_reporting(E_ALL);
*/
ini_set('display_errors', 'on');

/*******************************************
* file includes
*******************************************/

// admin only includes
if(is_admin()) {
	//require($rcp_base_dir . '/update-notifier.php');
	if(!class_exists('Custom_Plugin_Updater')) {
		include_once($rcp_base_dir . '/class-custom-plugin-updater.php' );
	}
	require($rcp_base_dir . '/includes/install.php');
	include($rcp_base_dir . '/includes/upgrades.php');
	include($rcp_base_dir . '/includes/admin-pages.php');
	include($rcp_base_dir . '/includes/admin-pages/screen-options.php');
	include($rcp_base_dir . '/includes/admin-pages/members-page.php');
	include($rcp_base_dir . '/includes/admin-pages/settings.php');
	include($rcp_base_dir . '/includes/admin-pages/subscription-levels.php');
	include($rcp_base_dir . '/includes/admin-pages/discount-codes.php');
	include($rcp_base_dir . '/includes/admin-pages/help-menus.php');
	include($rcp_base_dir . '/includes/admin-pages/payments-page.php');
	include($rcp_base_dir . '/includes/admin-pages/export.php');
	include($rcp_base_dir . '/includes/admin-pages/help-page.php');
	include($rcp_base_dir . '/includes/user-page-columns.php');
	include($rcp_base_dir . '/includes/metabox.php');
	include($rcp_base_dir . '/includes/process-data.php');
	include($rcp_base_dir . '/includes/export-functions.php');
	include($rcp_base_dir . '/includes/admin-notices.php');	
	include($rcp_base_dir . '/includes/admin-ajax-actions.php');	
	
	// setup the plugin updater
	$rcp_updater = new Custom_Plugin_Updater( 'http://pippinsplugins.com/updater/api/', RCP_PLUGIN_FILE, array());
	
}

// global includes
include($rcp_base_dir . '/includes/gateways/paypal/paypal.php');
include($rcp_base_dir . '/includes/misc-functions.php');
include($rcp_base_dir . '/includes/scripts.php');
include($rcp_base_dir . '/includes/member-functions.php');
include($rcp_base_dir . '/includes/discount-functions.php');
include($rcp_base_dir . '/includes/subscription-functions.php');
include($rcp_base_dir . '/includes/email-functions.php');
include($rcp_base_dir . '/includes/payment-tracking-functions.php');
include($rcp_base_dir . '/includes/handle-registration-login.php');
include($rcp_base_dir . '/includes/gateway-functions.php');
include($rcp_base_dir . '/includes/cron-functions.php');
include($rcp_base_dir . '/includes/ajax-actions.php');

// front-end only includes
if(!is_admin()) {
	include($rcp_base_dir . '/includes/shortcodes.php');
	include($rcp_base_dir . '/includes/member-forms.php');
	include($rcp_base_dir . '/includes/content-filters.php');
	include($rcp_base_dir . '/includes/feed-functions.php');
	if(isset($rcp_options['enable_recaptcha']) && $rcp_options['enable_recaptcha']) {
		require_once( $rcp_base_dir . '/includes/recaptchalib.php');	
	}
	include($rcp_base_dir . '/includes/user-checks.php');
	include($rcp_base_dir . '/includes/query-filters.php');
	include($rcp_base_dir . '/includes/redirects.php');
}

