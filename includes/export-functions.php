<?php

function rcp_export_members() {
	if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-members' ) {

		global $wpdb;
		if( $_POST['rcp-subscription'] == 'all' ) {
			$sql = "SELECT ID, user_login, display_name, user_email, user_url FROM $wpdb->users
				LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
				WHERE meta_key = 'rcp_status'
				AND meta_value = '{$_POST['rcp-status']}';";
			$filename = 'restrict-content-pro-' . $_POST['rcp-status'] . '-members.csv';

		} else {
			$sql = "SELECT ID, user_login, display_name, user_email, user_url FROM $wpdb->users
				LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
				WHERE meta_key = 'rcp_status'
				AND meta_value = '{$_POST['rcp-status']}'
				AND ID IN (
					SELECT ID FROM $wpdb->users
					LEFT JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id
					WHERE meta_key = 'rcp_subscription_level'
					AND meta_value = '{$_POST['rcp-subscription']}'
				)
				;";
			$filename = 'restrict-content-pro-' . str_replace( ' ', '_', rcp_get_subscription_name( $_POST['rcp-subscription'] ) ) . '-' . $_POST['rcp-status'] . '-members.csv';
		}
		rcp_query_to_csv( $sql, $filename );
	}
}
add_action( 'admin_init', 'rcp_export_members' );

function rcp_export_payments() {
	if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'export-payments' ) {

		include RCP_PLUGIN_DIR . 'includes/class-rcp-export.php';
		include RCP_PLUGIN_DIR . 'includes/class-rcp-export-payments.php';

		$export = new RCP_Payments_Export;
		$export->export();
	}
}
add_action( 'admin_init', 'rcp_export_payments' );