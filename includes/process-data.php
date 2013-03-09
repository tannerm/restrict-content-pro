<?php

/*************************************************************************
* this file processes all new subscription creations and updates
* also manages adding/editings subscriptions to users
* User registration and login is handled in handle-registration-login.php
**************************************************************************/
function rcp_process_data() {

	if( ! is_admin() )
		return;

	if( ! current_user_can( 'manage_options' ) )
		return;

	global $wpdb, $rcp_db_name;

	$rcp_post = ( !empty( $_POST ) ) ? true : false;
	if( $rcp_post ) {

		/****************************************
		* subscription levels
		****************************************/

		// add a new subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-level' ) {

			$levels = new RCP_Levels();

			$add = $levels->insert( $_POST );

			if( $add ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-added=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-added=0';
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-subscription') {

			if($_POST['duration'] == '' || $_POST['duration'] == 0) {
				$duration = 'unlimited';
			} else {
				$duration = $_POST['duration'];
			}
			$update = $wpdb->query(
				$wpdb->prepare(
					"UPDATE " . $rcp_db_name . " SET
						`name`='%s',
						`description`='%s',
						`duration`='%d',
						`duration_unit`='%s',
						`price`='%s',
						`level`='%d',
						`status`='%s'
						WHERE `id`='%d'
					;",
					utf8_encode( $_POST['name'] ),
					addslashes( utf8_encode( $_POST['description'] ) ),
					$duration,
					$_POST['duration-unit'],
					$_POST['price'],
					$_POST['level'],
					$_POST['status'],
					absint( $_POST['subscription_id'] )
				)
			);
			if($update) {
				// clear the cache
				delete_transient( 'rcp_subscription_levels' );
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-updated=1';
				do_action( 'rcp_edit_subscription', $_POST['subscription_id'], $_POST );
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&level-updated=0';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a subscription for an existing member
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-subscription' ) {

			if ( $_POST['expiration'] &&  strtotime( 'NOW' ) > strtotime( $_POST['expiration'] ) ) :

				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&user-added=0';
				header( "Location:" . $url );

			else:

				$user = get_user_by( 'login', $_POST['user'] );

				update_user_meta( $user->ID, 'rcp_status', 'active' );
				update_user_meta( $user->ID, 'rcp_expiration', $_POST['expiration'] );
				update_user_meta( $user->ID, 'rcp_subscription_level', $_POST['level'] );
				update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );
				if( isset( $_POST['recurring'] ) ) {
					update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
				} else {
					delete_user_meta( $user->ID, 'rcp_recurring' );
				}
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&user-added=1';
				header( "Location:" .  $url);

			endif;

		}

		// edit a member's subscription
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-member' ) {

			$user_id  = absint( $_POST['user'] );
			$status   = sanitize_text_field( $_POST['status'] );
			$expires  = sanitize_text_field( $_POST['expiration'] );
			$level    = absint( $_POST['level'] );

			if( isset( $_POST['status'] ) ) update_user_meta($user_id, 'rcp_status', $status );
			if( isset( $_POST['expiration'] ) && strlen( trim( $expires ) ) > 0 ) update_user_meta( $user_id, 'rcp_expiration', $expires );
			if( isset( $_POST['level'] ) ) update_user_meta( $user_id, 'rcp_subscription_level', $level );
			if( isset( $_POST['recurring'] ) ) {
				update_user_meta( $user_id, 'rcp_recurring', 'yes' );
			} else {
				delete_user_meta( $user_id, 'rcp_recurring' );
			}
			if( isset( $_POST['signup_method'] ) ) update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
			if( isset( $_POST['notes'] ) ) update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );

			wp_redirect( admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id . '&updated=true' ) ); exit;
		}


		/****************************************
		* discount codes
		****************************************/

		// add a new discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-discount' ) {

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'        => $_POST['name'],
				'description' => $_POST['description'],
				'amount'      => $_POST['amount'],
				'unit'        => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'        => $_POST['code'],
				'status'      => 'active',
				'expiration'  => $_POST['expiration'],
				'max_uses'    => $_POST['max']
			);

			$add = $discounts->insert( $data );

			if( $add ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-added=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-added=0';
			}

			wp_safe_redirect( $url ); exit;
		}

		// edit a discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-discount' ) {

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'        => $_POST['name'],
				'description' => $_POST['description'],
				'amount'      => $_POST['amount'],
				'unit'        => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'        => $_POST['code'],
				'status'      => $_POST['status'],
				'expiration'  => $_POST['expiration'],
				'max_uses'    => $_POST['max']
			);

			$update = $discounts->update( $_POST['discount_id'], $data );

			if( $update ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=0';
			}

			wp_safe_redirect( $url ); exit;
		}

	}

	/*************************************
	* delete data
	*************************************/
	$rcp_get = ( !empty( $_GET) ) ? true : false;

	if( $rcp_get ) // if data is being sent
	{

		/* member processing */
		if( isset( $_GET['deactivate_member'] ) ) {
			update_user_meta( urldecode( absint( $_GET['deactivate_member'] ) ), 'rcp_status', 'cancelled' );
		}
		if( isset( $_GET['activate_member'] ) ) {
			update_user_meta( urldecode( absint( $_GET['activate_member'] ) ), 'rcp_status', 'active' );
		}

		/* subscription processing */
		if( isset( $_GET['delete_subscription'] ) && $_GET['delete_subscription'] > 0) {
			$members_of_subscription = rcp_get_members_of_subscription( absint( $_GET['delete_subscription'] ) );

			// cancel all active members of this subscription
			if( $members_of_subscription ) {
				foreach( $members_of_subscription as $member ) {
					rcp_set_status( $member, 'cancelled' );
				}
			}
			$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM " . $rcp_db_name . " WHERE `id`='%d';", urldecode( absint( $_GET['delete_subscription'] ) ) ) );
			delete_transient( 'rcp_subscription_levels' );
		}
		if( isset( $_GET['activate_subscription'] ) && $_GET['activate_subscription'] > 0) {
			$wpdb->update($rcp_db_name, array('status' => 'active' ), array('id' => absint( $_GET['activate_subscription'] ) ) );
			delete_transient( 'rcp_subscription_levels' );
		}
		if( isset( $_GET['deactivate_subscription'] ) && $_GET['deactivate_subscription'] > 0) {
			$wpdb->update( $rcp_db_name, array( 'status' => 'inactive' ), array('id' => absint( $_GET['deactivate_subscription'] ) ) );
			delete_transient( 'rcp_subscription_levels' );
		}

		/* discount processing */
		if( ! empty( $_GET['delete_discount'] ) ) {
			$discounts = new RCP_Discounts();
			$discounts->delete( $_GET['delete_discount'] );
		}
		if( ! empty( $_GET['activate_discount'] ) ) {
			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['activate_discount'], array( 'status' => 'active' ) );
		}
		if( ! empty( $_GET['deactivate_discount'] ) ) {
			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['deactivate_discount'], array( 'status' => 'disabled' ) );
		}
	}
}
add_action( 'admin_init', 'rcp_process_data' );