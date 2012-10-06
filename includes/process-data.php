<?php

/*************************************************************************
* this file processes all new subscription creations and updates
* also manages adding/editings subscriptions to users
* User registration and login is handled in handle-registration-login.php
**************************************************************************/
function rcp_process_data() {

	if( ! is_admin() )
		return;

	global $wpdb, $rcp_db_name, $rcp_discounts_db_name;

	$rcp_post = ( !empty( $_POST ) ) ? true : false;
	if( $rcp_post ) {
	
		/****************************************
		* subscription levels
		****************************************/
	
		// add a new subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-level' ) {
	
			if( $_POST['duration'] == '' || $_POST['duration'] == 0 ) {
				$duration = 'unlimited';
			} else {
				$duration = $_POST['duration'];
			}
			
			$name = 'no name';
			if( isset( $_POST['name']) && $_POST['name'] != '') $name = $_POST['name'];
			
			$duration_unit = 'm';
			if( isset( $_POST['duration-unit'] ) ) $duration_unit = $_POST['duration-unit'];
			
			$price = 0;
			if( isset( $_POST['price']) && $_POST['price'] != '') $price = $_POST['price'];
			
			$level = 0;
			if( isset( $_POST['level']) && $_POST['level'] != '') $level = $_POST['level'];
			
			$description = '';
			if( isset( $_POST['description']) && $_POST['description'] != '') $description = $_POST['description'];
			
			$add = $wpdb->query( $wpdb->prepare( "INSERT INTO `" . $rcp_db_name . "` SET 
				`name`='" . utf8_encode($name) . "',
				`description`='" . addslashes(utf8_encode($description)) . "',
				`duration`='" . $duration . "',
				`duration_unit`='" . $duration_unit . "',
				`price`='" . $price . "',
				`list_order`='0',
				`level`='" . $level . "',
				`status`='" . $_POST['status'] . "'
			;" ) );	
			if($add) {
				// clear the cache
				delete_transient('rcp_subscription_levels');
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-added=1';
				do_action('rcp_add_subscription', $_POST);	
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-added=0';
			}
			header ("Location:" . $url);
		}
	
		// edit a subscription level
		if( isset( $_POST['rcp-action']) && $_POST['rcp-action'] == 'edit-subscription') {
	
			if($_POST['duration'] == '' || $_POST['duration'] == 0) {
				$duration = 'unlimited';
			} else {
				$duration = $_POST['duration'];
			}
			$update = $wpdb->query( $wpdb->prepare( "UPDATE " . $rcp_db_name . " SET 
				`name`='" . utf8_encode($_POST['name']) . "',
				`description`='" . addslashes(utf8_encode($_POST['description'] ) ) . "',
				`duration`='" . $duration . "',
				`duration_unit`='" . $_POST['duration-unit'] . "',
				`price`='" . $_POST['price'] . "',
				`level`='" . $_POST['level'] . "',
				`status`='" . $_POST['status'] . "'
				WHERE `id` ='" .  $_POST['subscription_id'] . "'
			;" ) );	
			if($update) {
				// clear the cache
				delete_transient('rcp_subscription_levels');
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-updated=1';
				do_action('rcp_edit_subscription', $_POST['subscription_id'], $_POST);			
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-updated=0';
			}

			header ("Location:" . $url);
		}
	
		// add a subscription for an existing member
		if( isset($_POST['rcp-action']) && $_POST['rcp-action'] == 'add-subscription' ) {
		
			if ( $_POST['expiration'] &&  strtotime('NOW') > strtotime($_POST['expiration']) ) :
			
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&user-added=0';
				header ("Location:" . $url);
		
			else:
				
				$user = get_user_by('login', $_POST['user']);				
				
				update_user_meta($user->ID, 'rcp_status', 'active');
				update_user_meta($user->ID, 'rcp_expiration', $_POST['expiration']);
				update_user_meta($user->ID, 'rcp_subscription_level', $_POST['level']);
				update_user_meta($user->ID, 'rcp_signup_method', 'manual');
			
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&user-added=1';
				header ("Location:" .  $url);
			
			endif;
		
		}
	
		// edit a member's subscription
		if( isset($_POST['rcp-action']) && $_POST['rcp-action'] == 'edit-member' ) {
		
			$user_id = $_POST['user'];
			$status = $_POST['status'];
			$expires = $_POST['expiration'];
			$level = $_POST['level'];
		
			if( isset( $_POST['status'] ) ) update_user_meta($user_id, 'rcp_status', $status);
			if( isset( $_POST['expiration']) && strlen(trim($expires)) > 0) update_user_meta($user_id, 'rcp_expiration', $expires);
			if( isset( $_POST['level'] ) ) update_user_meta($user_id, 'rcp_subscription_level', $level);
			if( isset( $_POST['recurring'] ) ) {
				update_user_meta($user_id, 'rcp_recurring', 'yes'); 
			} else {
				update_user_meta($user_id, 'rcp_recurring', 'no');
			}
			if( isset( $_POST['signup_method'] ) ) update_user_meta($user_id, 'rcp_signup_method', $_POST['signup_method']);
		
			$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&user-updated=1';
			header ("Location:" . $url);
		}
		
		
		/****************************************
		* discount codes
		****************************************/
		
		// add a new discount code
		if( isset( $_POST['rcp-action']) && $_POST['rcp-action'] == 'add-discount') {
			
			$name = 'no name';
			if( isset( $_POST['name']) && $_POST['name'] != '') $name = $_POST['name'];
			
			$description = '';
			if( isset( $_POST['description']) && $_POST['description'] != '') $description = $_POST['description'];
			
			$amount = 0;
			if( isset( $_POST['amount']) && $_POST['amount'] != '') $amount = $_POST['amount'];
			
			$expiration = '';
			if( isset( $_POST['expiration']) && $_POST['expiration'] != '') $expiration = $_POST['expiration'];
			
			$max = '';
			if( isset( $_POST['max']) && $_POST['max'] != '') $max = $_POST['max'];
			
			$add = $wpdb->query( $wpdb->prepare( "INSERT INTO `" . $rcp_discounts_db_name . "` SET 
				`name`='" . $name . "',
				`description`='" . addslashes($description) . "',
				`amount`='" . $amount . "',
				`status`='active',
				`unit`='" . $_POST['unit'] . "',
				`code`='" . $_POST['code'] . "',
				`expiration`='" . $expiration . "',
				`max_uses`='" . $max . "',
				`use_count`='0'
			;" ) );
			
			do_action('rcp_add_discount', $_POST);
			
			if($add) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-added=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-added=0';
			}
			header ("Location:" . $url);
		}
	
		// edit a discount code
		if( isset( $_POST['rcp-action']) && $_POST['rcp-action'] == 'edit-discount') {
	
			$update = $wpdb->query( $wpdb->prepare( "UPDATE " . $rcp_discounts_db_name . " SET 
				`name`='" . $_POST['name'] . "',
				`description`='" . addslashes($_POST['description']) . "',
				`amount`='" . $_POST['amount'] . "',
				`unit`='" . $_POST['unit'] . "',
				`code`='" . $_POST['code'] . "',
				`status`='" . $_POST['status'] . "',
				`expiration`='" . $_POST['expiration'] . "',
				`max_uses`='" . $_POST['max'] . "'
				WHERE `id` ='" .  $_POST['discount_id'] . "'
			;" ) );
			
			do_action('rcp_edit_discount', $_POST);
			
			if($update) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=0';
			}
			header ("Location:" . $url);
		}
	
	} 

	/*************************************
	* delete data
	*************************************/
	$rcp_get = (!empty($_GET)) ? true : false;

	if($rcp_get) // if data is being sent
	{
		
		/* member processing */
		if( isset( $_GET['deactivate_member'] ) ) {
			update_user_meta(urldecode($_GET['deactivate_member']), 'rcp_status', 'cancelled');
		}
		if( isset( $_GET['activate_member'] ) ) {
			update_user_meta(urldecode($_GET['activate_member']), 'rcp_status', 'active');
		}
		
		/* subscription processing */
		if( isset( $_GET['delete_subscription']) && $_GET['delete_subscription'] > 0) {
			$members_of_subscription = rcp_get_members_of_subscription($_GET['delete_subscription']);

			// cancel all active members of this subscription
			if($members_of_subscription) {
				foreach($members_of_subscription as $member) {
					rcp_set_status($member, 'cancelled');
				}
			}
			$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $rcp_db_name . " WHERE `id`='" . urldecode($_GET['delete_subscription']) . "';") );
			delete_transient('rcp_subscription_levels');
		}
		if( isset( $_GET['activate_subscription']) && $_GET['activate_subscription'] > 0) {
			$wpdb->update($rcp_db_name, array('status' => 'active' ), array('id' => $_GET['activate_subscription'] ) );
			delete_transient('rcp_subscription_levels');
		}
		if( isset( $_GET['deactivate_subscription']) && $_GET['deactivate_subscription'] > 0) {
			$wpdb->update($rcp_db_name, array('status' => 'inactive' ), array('id' => $_GET['deactivate_subscription'] ) );
			delete_transient('rcp_subscription_levels');
		}
		
		/* discount processing */
		if( isset( $_GET['delete_discount']) && $_GET['delete_discount'] > 0) {
			$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $rcp_discounts_db_name . " WHERE `id`='" . urldecode($_GET['delete_discount']) . "';" ) );
		}
		if( isset( $_GET['activate_discount']) && $_GET['activate_discount'] > 0) {
			$wpdb->update($rcp_discounts_db_name, array('status' => 'active' ), array('id' => $_GET['activate_discount'] ) );
		}
		if( isset( $_GET['deactivate_discount']) && $_GET['deactivate_discount'] > 0) {
			$wpdb->update($rcp_discounts_db_name, array('status' => 'disabled' ), array('id' => $_GET['deactivate_discount'] ) );
		}
	}
}
add_action('init', 'rcp_process_data');