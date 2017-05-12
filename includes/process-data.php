<?php
/**
 * Process Data
 *
 * This file processes all new subscription creations and updates.
 * It also manages adding/editing subscriptions to users.
 * The user registration and login is handled in registration-functions.php
 *
 * @package     Restrict Content Pro
 * @subpackage  Process Data
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Handles admin processing for:
 *
 * New subscription creations and updates
 * Adding/editing subscriptions to users
 * Adding/editing discount codes
 *
 * @return void
 */
function rcp_process_data() {

	if( ! is_admin() )
		return;

	if( ! empty( $_POST ) ) {

		/****************************************
		* subscription levels
		****************************************/

		// add a new subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-level' ) {

			if( ! wp_verify_nonce( $_POST['rcp_add_level_nonce'], 'rcp_add_level_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			if( empty( $_POST['name'] ) ) {
				$url = admin_url( 'admin.php?page=rcp-member-levels&rcp_message=level_missing_fields' );
				wp_safe_redirect( esc_url_raw( $url ) ); exit;
			}

			$levels = new RCP_Levels();

			$level_id = $levels->insert( $_POST );

			if( $level_id ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_added';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_added';
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a subscription level
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-subscription') {

			if( ! wp_verify_nonce( $_POST['rcp_edit_level_nonce'], 'rcp_edit_level_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$level_id = absint( $_POST['subscription_id'] );

			$levels = new RCP_Levels();

			$update = $levels->update( $_POST['subscription_id'], $_POST );

			if($update) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_updated';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-member-levels&rcp_message=level_not_updated';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a subscription for an existing member
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-subscription' ) {

			if( ! wp_verify_nonce( $_POST['rcp_add_member_nonce'], 'rcp_add_member_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			if ( empty( $_POST['level'] ) || empty( $_POST['user'] ) ) {
				wp_die( __( 'Please fill out all fields.', 'rcp' ) );
			}

			if ( isset( $_POST['expiration'] ) &&  strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) && 'none' !== $_POST['expiration'] ) :

				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_not_added';
				header( "Location:" . $url );

			else:

				$levels = new RCP_Levels();

				$user   = get_user_by( 'login', $_POST['user'] );

				if ( ! $user ) {
					wp_die( __( 'You entered a username that does not exist.', 'rcp' ) );
				}

				$member = new RCP_Member( $user->ID );

				$expiration = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';

				$level_id   = absint( $_POST['level'] );

				$subscription = $levels->get_level( $level_id );

				if ( ! $subscription ) {
					wp_die( __( 'Please supply a valid subscription level.', 'rcp' ) );
				}

				$member->set_expiration_date( $expiration );

				$new_subscription = get_user_meta( $user->ID, '_rcp_new_subscription', true );

				if ( empty( $new_subscription ) ) {
					update_user_meta( $user->ID, '_rcp_new_subscription', '1' );
				}

				update_user_meta( $user->ID, 'rcp_signup_method', 'manual' );

				$member->set_subscription_id( $level_id );

				$status = $subscription->price == 0 ? 'free' : 'active';

				$member->set_status( $status );

				// Add the new user role
				$role = ! empty( $subscription->role ) ? $subscription->role : 'subscriber';
				$user->add_role( $role );

				// Set joined date for the new subscription
				$member->set_joined_date( '', $level_id );

				if( isset( $_POST['recurring'] ) ) {
					update_user_meta( $user->ID, 'rcp_recurring', 'yes' );
				} else {
					delete_user_meta( $user->ID, 'rcp_recurring' );
				}

				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members&rcp_message=user_added';
				header( "Location:" .  $url);

			endif;

		}

		// bulk edit members
		if( isset( $_POST['rcp-bulk-action'] ) && $_POST['rcp-bulk-action'] ) {

			if( ! wp_verify_nonce( $_POST['rcp_bulk_edit_nonce'], 'rcp_bulk_edit_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			if( empty( $_POST['member-ids'] ) ) {
				wp_die( __( 'Please select at least one member to edit.', 'rcp' ) );
			}

			$member_ids = array_map( 'absint', $_POST['member-ids'] );
			$action     = ! empty( $_POST['rcp-bulk-action'] ) ? sanitize_text_field( $_POST['rcp-bulk-action'] ) : false;

			foreach( $member_ids as $member_id ) {

				$member = new RCP_Member( $member_id );

				if( ! empty( $_POST['expiration'] ) && 'delete' !== $action ) {
					$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) );
				}

				if( $action ) {

					switch( $action ) {

						case 'mark-active' :

							$member->set_status( 'active' );

							break;

						case 'mark-expired' :

							$member->set_status( 'expired' );

							break;

						case 'mark-cancelled' :

							$member->cancel();

							if( ! empty( $_POST['rcp-revoke-access'] ) && ! $member->is_expired() ) {
								$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );
							}

							break;

					}

				}

			}

			wp_redirect( admin_url( 'admin.php?page=rcp-members&rcp_message=members_updated' ) ); exit;

		}

		// edit a member's subscription
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-member' ) {

			if( ! wp_verify_nonce( $_POST['rcp_edit_member_nonce'], 'rcp_edit_member_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels        = new RCP_Levels();
			$user_id       = absint( $_POST['user'] );
			$member        = new RCP_Member( $user_id );
			$email         = sanitize_text_field( $_POST['email'] );
			$status        = sanitize_text_field( $_POST['status'] );
			$level_id      = absint( $_POST['level'] );
			$expiration    = isset( $_POST['expiration'] ) ? sanitize_text_field( $_POST['expiration'] ) : 'none';
			$expiration    = 'none' !== $expiration ? date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ) : $expiration;
			$revoke_access = isset( $_POST['rcp-revoke-access'] );

			if( isset( $_POST['notes'] ) ) {
				update_user_meta( $user_id, 'rcp_notes', wp_kses( $_POST['notes'], array() ) );
			}

			if( ! empty( $_POST['expiration'] ) && ( 'cancelled' != $status || ! $revoke_access ) ) {
				$member->set_expiration_date( $expiration );
			} elseif( 'cancelled' == $status && $revoke_access && ! $member->is_expired() ) {
				$member->set_expiration_date( date( 'Y-m-d H:i:s', strtotime( '-1 day', current_time( 'timestamp' ) ) ) );
			}

			if( isset( $_POST['level'] ) ) {

				$current_id = rcp_get_subscription_id( $user_id );
				$new_level  = $levels->get_level( $level_id );
				$old_level  = $levels->get_level( $current_id );

				if( $current_id != $level_id ) {

					$member->set_subscription_id( $level_id );

					// Remove the old user role
					$role = ! empty( $old_level->role ) ? $old_level->role : 'subscriber';
					$member->remove_role( $role );

					// Add the new user role
					$role = ! empty( $new_level->role ) ? $new_level->role : 'subscriber';
					$member->add_role( $role );

					// Set joined date for the new subscription
					$member->set_joined_date( '', $level_id );

				}
			}

			if( isset( $_POST['recurring'] ) ) {
				$member->set_recurring( true );
			} else {
				$member->set_recurring( false );
			}

			if( isset( $_POST['trialing'] ) ) {
				update_user_meta( $user_id, 'rcp_is_trialing', 'yes' );
			} else {
				delete_user_meta( $user_id, 'rcp_is_trialing' );
			}

			if( isset( $_POST['signup_method'] ) ) {
				update_user_meta( $user_id, 'rcp_signup_method', $_POST['signup_method'] );
			}

			if( isset( $_POST['cancel_subscription'] ) && $member->can_cancel() ) {
				$member->cancel_payment_profile();
			}

			if( $status !== $member->get_status() ) {
				$member->set_status( $status );
			}

			if( isset( $_POST['payment-profile-id'] ) ) {
				$member->set_payment_profile_id( $_POST['payment-profile-id'] );
			}

			if( $email != $member->user_email ) {
				wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) );
			}

			do_action( 'rcp_edit_member', $user_id );

			wp_redirect( admin_url( 'admin.php?page=rcp-members&edit_member=' . $user_id . '&rcp_message=user_updated' ) ); exit;

		}

		/****************************************
		* discount codes
		****************************************/

		// add a new discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-discount' ) {

			if( ! wp_verify_nonce( $_POST['rcp_add_discount_nonce'], 'rcp_add_discount_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'            => $_POST['name'],
				'description'     => $_POST['description'],
				'amount'          => $_POST['amount'],
				'unit'            => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'            => $_POST['code'],
				'status'          => 'active',
				'expiration'      => $_POST['expiration'],
				'max_uses'        => $_POST['max'],
				'subscription_id' => $_POST['subscription']
			);

			$add = $discounts->insert( $data );

			if ( is_wp_error( $add ) ) {
				wp_die( $add );
			}

			if( $add ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&rcp_message=discount_added';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&rcp_message=discount_not_added';
			}

			wp_safe_redirect( $url ); exit;
		}

		// edit a discount code
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-discount' ) {

			if( ! wp_verify_nonce( $_POST['rcp_edit_discount_nonce'], 'rcp_edit_discount_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();

			// Setup unsanitized data
			$data = array(
				'name'            => $_POST['name'],
				'description'     => $_POST['description'],
				'amount'          => $_POST['amount'],
				'unit'            => isset( $_POST['unit'] ) && $_POST['unit'] == '%' ? '%' : 'flat',
				'code'            => $_POST['code'],
				'status'          => $_POST['status'],
				'expiration'      => $_POST['expiration'],
				'max_uses'        => $_POST['max'],
				'subscription_id' => $_POST['subscription']
			);

			$update = $discounts->update( $_POST['discount_id'], $data );

			if ( is_wp_error( $update ) ) {
				wp_die( $update );
			}

			if( $update ) {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=1';
			} else {
				$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-discounts&discount-updated=0';
			}

			wp_safe_redirect( $url ); exit;
		}

		// add a new manual payment
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'add-payment' ) {

			if( ! wp_verify_nonce( $_POST['rcp_add_payment_nonce'], 'rcp_add_payment_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();

			$user = get_user_by( 'login', $_POST['user'] );

			if( $user ) {

				$data = array(
					'amount'           => empty( $_POST['amount'] ) ? 0.00 : sanitize_text_field( $_POST['amount'] ),
					'user_id'          => $user->ID,
					'date'             => empty( $_POST['date'] ) ? date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) : date( 'Y-m-d', strtotime( $_POST['date'], current_time( 'timestamp' ) ) ) . ' ' . date( 'H:i:s', current_time( 'timestamp' ) ),
					'payment_type'     => 'manual',
					'subscription'     => rcp_get_subscription( $user->ID ),
					'subscription_key' => rcp_get_subscription_key( $user->ID ),
					'transaction_id'   => sanitize_text_field( $_POST['transaction-id'] ),
					'status'           => sanitize_text_field( $_POST['status'] ),
				);

				$add = $payments->insert( $data );

			}

			if( ! empty( $add ) ) {
				$cache_args = array( 'earnings' => 1, 'subscription' => 0, 'user_id' => 0, 'date' => '' );
				$cache_key  = md5( implode( ',', $cache_args ) );
				delete_transient( $cache_key );

				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_added' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_not_added' );
			}
			wp_safe_redirect( $url ); exit;
		}

		// edit a payment
		if( isset( $_POST['rcp-action'] ) && $_POST['rcp-action'] == 'edit-payment' ) {

			if( ! wp_verify_nonce( $_POST['rcp_edit_payment_nonce'], 'rcp_edit_payment_nonce' ) ) {
				wp_die( __( 'Nonce verification failed.', 'rcp' ) );
			}

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();

			$payment_id = absint( $_POST['payment-id'] );
			$user      = get_user_by( 'login', $_POST['user'] );

			if( $user && $payment_id ) {

				$data = array(
					'amount'           => empty( $_POST['amount'] ) ? 0.00 : sanitize_text_field( $_POST['amount'] ),
					'user_id'          => $user->ID,
					'date'             => empty( $_POST['date'] ) ? date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) : date( 'Y-m-d', strtotime( $_POST['date'], current_time( 'timestamp' ) ) ) . ' ' . date( 'H:i:s', current_time( 'timestamp' ) ),
					'subscription'     => rcp_get_subscription( $user->ID ),
					'subscription_key' => rcp_get_subscription_key( $user->ID ),
					'transaction_id'   => sanitize_text_field( $_POST['transaction-id'] ),
					'status'           => sanitize_text_field( $_POST['status'] ),
				);

				$update = $payments->update( $payment_id, $data );

			}

			if( ! empty( $update ) ) {
				$cache_args = array( 'earnings' => 1, 'subscription' => 0, 'user_id' => 0, 'date' => '' );
				$cache_key  = md5( implode( ',', $cache_args ) );
				delete_transient( $cache_key );

				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_updated' );
			} else {
				$url = admin_url( 'admin.php?page=rcp-payments&rcp_message=payment_not_updated' );
			}
			wp_safe_redirect( $url ); exit;
		}

	}

	/*************************************
	* delete data
	*************************************/
	if( ! empty( $_GET ) ) {

		/* member processing */
		if( isset( $_GET['cancel_member'] ) ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			rcp_cancel_member_payment_profile( urldecode( absint( $_GET['cancel_member'] ) ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'member_cancelled', 'admin.php?page=rcp-members' ) ) ); exit;
		}

		if( isset( $_GET['send_verification'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'rcp-verification-nonce' ) ) {

			if( ! current_user_can( 'rcp_manage_members' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			rcp_send_email_verification( urldecode( absint( $_GET['send_verification'] ) ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'verification_sent', 'admin.php?page=rcp-members' ) ) ); exit;

		}

		/* subscription processing */
		if( isset( $_GET['delete_subscription'] ) && $_GET['delete_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$members_of_subscription = rcp_get_members_of_subscription( absint( $_GET['delete_subscription'] ) );

			// cancel all active members of this subscription
			if( $members_of_subscription ) {
				foreach( $members_of_subscription as $member ) {
					rcp_set_status( $member, 'cancelled' );
				}
			}
			$levels = new RCP_Levels();
			$levels->remove( $_GET['delete_subscription'] );
			$levels->remove_all_meta_for_level_id( $_GET['delete_subscription'] );

		}
		if( isset( $_GET['activate_subscription'] ) && $_GET['activate_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();
			$update = $levels->update( absint( $_GET['activate_subscription'] ), array( 'status' => 'active' ) );
			delete_transient( 'rcp_subscription_levels' );
		}
		if( isset( $_GET['deactivate_subscription'] ) && $_GET['deactivate_subscription'] > 0) {

			if( ! current_user_can( 'rcp_manage_levels' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$levels = new RCP_Levels();
			$update = $levels->update( absint( $_GET['deactivate_subscription'] ), array( 'status' => 'inactive' ) );
			delete_transient( 'rcp_subscription_levels' );
		}

		/* discount processing */
		if( ! empty( $_GET['delete_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->delete( $_GET['delete_discount'] );
		}
		if( ! empty( $_GET['activate_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['activate_discount'], array( 'status' => 'active' ) );
		}
		if( ! empty( $_GET['deactivate_discount'] ) ) {

			if( ! current_user_can( 'rcp_manage_discounts' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$discounts = new RCP_Discounts();
			$discounts->update( $_GET['deactivate_discount'], array( 'status' => 'disabled' ) );
		}
		if( ! empty( $_GET['rcp-action'] ) && $_GET['rcp-action'] == 'delete_payment' && wp_verify_nonce( $_GET['_wpnonce'], 'rcp_delete_payment_nonce' ) ) {

			if( ! current_user_can( 'rcp_manage_payments' ) ) {
				wp_die( __( 'You do not have permission to perform this action.', 'rcp' ) );
			}

			$payments = new RCP_Payments();
			$payments->delete( absint( $_GET['payment_id'] ) );
			wp_safe_redirect( admin_url( add_query_arg( 'rcp_message', 'payment_deleted', 'admin.php?page=rcp-payments' ) ) ); exit;
		}

	}
}
add_action( 'admin_init', 'rcp_process_data' );
