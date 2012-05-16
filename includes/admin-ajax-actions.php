<?php

function rcp_ajax_get_subscription_expiration() {
	if(isset($_POST['subscription_level'])) {
		$level_id = $_POST['subscription_level'];
		$expiration = rcp_calculate_subscription_expiration($level_id);
		echo $expiration;
	}
	die();
}
add_action('wp_ajax_rcp_get_subscription_expiration', 'rcp_ajax_get_subscription_expiration');

// processes the ajax re-ordering request
function rcp_update_subscription_order() {
	if(isset($_POST['recordsArray'])) {
		global $wpdb, $rcp_db_name;
		$updateRecordsArray = $_POST['recordsArray'];
		$listingCounter = 1;
		foreach ($updateRecordsArray as $recordIDValue) {
			$new_order = $wpdb->update(
				$rcp_db_name, 
				array('list_order' => $listingCounter ), 
				array('id' => $recordIDValue),
				array('%d')
			);
			$listingCounter++;
		}
		// clear the cache
		delete_transient('rcp_subscription_levels');
	}
	die();
	
}
add_action('wp_ajax_update-subscription-order', 'rcp_update_subscription_order');

/*
* Check whether a discount code is valid. Used during registration to validate a discount code on the fly
* @param - string $code - the discount code to validate
* return none
*/
function rcp_validate_discount_with_ajax() {
	if(isset($_POST['code'])) {
		if(rcp_validate_discount($_POST['code'])) {
			$code_details = rcp_get_discount_details_by_code($_POST['code']);
			if($code_details && $code_details->amount == 100 && $code_details->unit == '%') {
				// this is a 100% discount
				echo 'valid and full';
			} else {
				echo 'valid';
			}
		} else {
			echo 'invalid';
		}
	}
	die();
}
add_action('wp_ajax_validate_discount', 'rcp_validate_discount_with_ajax');
add_action('wp_ajax_nopriv_validate_discount', 'rcp_validate_discount_with_ajax');