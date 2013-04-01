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
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_added';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_added';
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-subscription') {

			$levels = new RCP_Levels();

			$update = $levels->update( $_POST['subscription_id'], $_POST );

			if($update) {
				// clear the cache
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_updated';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_updated';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a subscription for an existing member
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-subscription' ) {

			if ( isset( $_POST['expiration'] ) &&  strtotime( 'NOW' ) > strtotime( $_POST['expiration'] ) ) :

				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_not_added';
				header( "Location:" . $url );

			else:

				$user = get_user_by( 'login', $_POST['user'] );

				$expiration = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';

				update_user_meta( $user->ID, 'rcp_status', 'active' );
				update_user_meta( $user->ID, 'rcp_expiration', $expiration );
				update_user_meta( $user->ID, 'rcp_subscription_level', $_POST['level'] );
				update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );
				if( isset( $_POST['recurring'] ) ) {
					update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
				} else {
					delete_user_meta( $user->ID, 'rcp_recurring' );
				}
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_added';
				header( "Location:" .  $url);

			endif;

		}

		// edit a member's subscription
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-member' ) {

			$user_id  = absint( $_POST['user'] );
			$status   = sanitize_text_field( $_POST['status'] );
			$level    = absint( $_POST['level'] );
			$expiration = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';

			if( isset( $_POST['status'] ) ) update_user_meta($user_id, 'rcp_status', $status );

			update_user_meta( $user_id, 'rcp_expiration', $expiration );

			if( isset( $_POST['level'] ) ) update_user_meta( $user_id, 'rcp_subscription_level', $level );
			if( isset( $_POST['recurring'] ) ) {
				update_user_meta( $user_id, 'rcp_recurring', 'yes' );
			} else {
				delete_user_meta( $user_id, 'rcp_recurring' );
			}
			if( isset( $_POST['signup_method'] ) ) update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
			if( isset( $_POST['notes'] ) ) update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );

			wp_redirect( admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id . '&rcp_message=user_updated' ) ); exit;
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
			$levels = new RCP_Levels();
			$levels->remove( $_GET['delete_subscription'] );

		}
		if( isset( $_GET['activate_subscription'] ) && $_GET['activate_subscription'] > 0) {
			$levels = new RCP_Levels();
			$update = $levels->update( $_GET['activate_subscription'], array( 'status' => 'active' ) );
			delete_transient( 'rcp_subscription_levels' );
		}
		if( isset( $_GET['deactivate_subscription'] ) && $_GET['deactivate_subscription'] > 0) {
			$levels = new RCP_Levels();
			$update = $levels->update( $_GET['deactivate_subscription'], array( 'status' => 'inactive' ) );
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
		if( ! empty( $_GET['rcp-action'] ) && $_GET['rcp-action'] == 'delete_payment' && wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_payment_nonce' ) ) {
			$payments = new RCP_Payments();
			$payments->delete( absint( $_GET['payment_id'] ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'payment_deleted', 'admin.php?page=rcp-payments' ) ) ); exit;
		}
	}
}
add_action( 'admin_init', 'rcp_process_data' );