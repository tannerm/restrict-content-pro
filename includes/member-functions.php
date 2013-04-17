<?php

/****************************************
* Functions for getting member info
*****************************************/

/*
* Returns an array of all members, based on subscription status
* @param string $status - the subscription status of users to retrieve
* @param int $subscription - the subscription ID to retrieve users from
* @param int $offset - the number of users to skip, used for pagination
* @param int $number - the total users to retrieve, used for pagination
* @param string $order - the order in which to display users: ASC / DESC
* @param string $recurring - retrieve recurring (or non recurring) only
* @param string $search - seach parameter
* Return array
*/
function rcp_get_members( $status = 'active', $subscription = null, $offset = 0, $number = 999999, $order = 'DESC', $recurring = null, $search = '' ) {

	global $wpdb;

	$args = array(
		'offset' => $offset,
		'number' => $number,
		'count_total' => false,
		'orderby' => 'ID',
		'order' => $order,
		'meta_query' => array(
			array(
				'key' => 'rcp_status',
				'value' => $status
			)
		)
	);

	if( ! empty( $subscription ) ) {
		$args['meta_query'][] = array(
			'key'   => 'rcp_subscription_level',
			'value' => $subscription
		);
	}

	if( ! empty( $recurring ) ) {
		if( $recurring == 1 ) {
			// find non recurring users

			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'compare' => 'NOT EXISTS'
			);
		} else {
			// find recurring users
			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'value'   => 'yes'
			);
		}
	}

	if( ! empty( $search ) ) {
		$args['search'] = sanitize_text_field( $search );
	}

	$members = get_users( $args );

	if( !empty( $members ) )
		return $members;

	return false;
}


/*
* Counts the number of members by subscription level and status
* @param string/int $level - the ID of the subscription level to count members of
* @param string - the status to count
* return int - the number of members for the specified subscription level and status
*/
function rcp_count_members( $level = '', $status = 'active', $recurring = null, $search = '' ) {
	global $wpdb;


	if( $status == 'free' ) {

		if ( ! empty( $level ) ) :

			$args = array(
				'meta_query' => array(
					array(
						'key' => 'rcp_subscription_level',
						'value' => $level,
					),
					array(
						'key'   => 'rcp_status',
						'value' => 'free'
					)
				)
			);

		else :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_status',
						'value' => 'free'
					)
				)
			);

		endif;

	} else {

		if ( ! empty( $level ) ) :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_subscription_level',
						'value' =>  $level
					),
					array(
						'key'   => 'rcp_status',
						'value' => $status
					)
				)
			);

		else :

			$args = array(
				'meta_query' => array(
					array(
						'key'   => 'rcp_status',
						'value' => $status
					)
				)
			);

		endif;

	}

	if( ! empty( $recurring ) ) {
		if( $recurring == 1 ) {
			// find non recurring users

			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'compare' => 'NOT EXISTS'
			);
		} else {
			// find recurring users
			$args['meta_query'][] = array(
				'key'     => 'rcp_recurring',
				'value'   => 'yes'
			);
		}
	}

	if( ! empty( $search ) ) {
		$args['search'] = sanitize_text_field( $search );
	}

	$args['fields'] = 'ID';
	$users = new WP_User_Query( $args );
	return $users->get_total();
}

/*
* Retrieves the total number of members by subscription status
* return array - an array of counts
*/
function rcp_count_all_members() {
	$counts = array(
		'active' 	=> rcp_count_members('', 'active'),
		'pending' 	=> rcp_count_members('', 'pending'),
		'expired' 	=> rcp_count_members('', 'expired'),
		'cancelled' => rcp_count_members('', 'cancelled'),
		'free' 		=> rcp_count_members('', 'free')
	);
	return $counts;
}

/*
* Gets all members of a particular subscription level
* @param int $id - the ID of the subscription level to retrieve users for
* @param mixed $fields - the user fields to restrieve. String or array
* return array - an array of user objects
*/
function rcp_get_members_of_subscription( $id = 1, $fields = 'ID') {
	$members = get_users(array(
			'meta_key' 		=> 'rcp_subscription_level',
			'meta_value' 	=> $id,
			'number' 		=> 0,
			'fields' 		=> $fields,
			'count_total' 	=> false
		)
	);
	return $members;
}

/*
* Gets a user's subscription level ID
* @param int $user_id - the ID of the user to return the subscription level of
* return int - the ID of the user's subscription level
*/
function rcp_get_subscription_id( $user_id ) {
	$subscription_id = get_user_meta( $user_id, 'rcp_subscription_level', true );
	return $subscription_id;
}

/*
* Gets a user's subscription level name
* @param int $user_id - the ID of the user to return the subscription level of
* return string - the name of the user's subscription level
*/
function rcp_get_subscription( $user_id ) {
	$subscription_id = get_user_meta( $user_id, 'rcp_subscription_level', true );
	$subscription = rcp_get_subscription_name( $subscription_id );
	return $subscription;
}


/*
* Checks whether a user has a recurring subscription
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user is recurring, false otherwise
*/
function rcp_is_recurring( $user_id = null ) {

	if( $user_id == null && is_user_logged_in() ) {
		global $user_ID;
		$user_id = $user_ID;
	}

	$recurring = get_user_meta( $user_id, 'rcp_recurring', true );
	if( $recurring == 'yes' ) {
		return true;
	}
	return false;
}


/*
* Checks whether a user is expired
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user is expired, false otherwise
*/
function rcp_is_expired( $user_id = null ) {

	if( $user_id == null && is_user_logged_in() ) {
		global $user_ID;
		$user_id = $user_ID;
	}

	$expiration = get_user_meta( $user_id, 'rcp_expiration', true );
	if( $expiration == 'none' ) {
		return false;
	}
	if( $expiration && strtotime('NOW') > strtotime( $expiration ) ) {
		return true;
	}
	return false;
}

/*
* Checks whether a user has an active subscription
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has an active, paid subscription (or is trialing), false otherwise
*/
function rcp_is_active( $user_id = 0 ) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( current_user_can( 'manage_options' ) ) {
		$ret = true;
	} else if( ! rcp_is_expired( $user_id ) && rcp_get_status( $user_id ) == 'active' && strlen( trim( rcp_get_subscription( $user_id ) ) ) > 0 ) {
		$ret = true;
	}
	return apply_filters( 'rcp_is_active', $ret, $user_id );
}

/*
* Just a wrapper function for rcp_is_active()
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has an active, paid subscription (or is trialing), false otherwise
*/
function rcp_is_paid_user( $user_id = 0) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( rcp_is_active( $user_id ) ) {
		$ret = true;
	}
	return apply_filters( 'rcp_is_paid_user', $ret, $user_id );
}

/*
* returns true if the user's subscription gives access to the provided access level
*/
function rcp_user_has_access( $user_id = 0, $access_level_needed ) {

	$subscription_level = rcp_get_subscription_id( $user_id );
	$user_access_level = rcp_get_subscription_access_level( $subscription_level );

	if( ( $user_access_level >= $access_level_needed ) || $access_level_needed == 0 || current_user_can( 'manage_options' ) ) {
		// the user has access
		return true;
	}

	// the user does not have access
	return false;
}

function rcp_calc_member_expiration( $expiration_object ) {
	$expiration_unit 	= $expiration_object->duration_unit;
	$expiration_length 	= $expiration_object->duration;
	$member_expires 	= date( 'Y-m-d H:i:s', strtotime( '+' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59' ) );

	return apply_filters( 'rcp_calc_member_expiration', $member_expires, $expiration_object );
}


/*
* Gets the date of a user's expiration in a nice format
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The date of the user's expiration, in the format specified in settings
*/
function rcp_get_expiration_date( $user_id ) {
	$expiration = get_user_meta( $user_id, 'rcp_expiration', true);
	if( $expiration ) {
		return $expiration != 'none' ? date_i18n( get_option('date_format'), strtotime( $expiration ) ) : __( 'none', 'rcp' );
	}
	return false;
}

/*
* Gets the date of a user's expiration in a unix time stamp
* @param int $user_id - the ID of the user to return the subscription level of
* return mixed - Timestamp of expiration of false if no expiration
*/
function rcp_get_expiration_timestamp( $user_id ) {
	$expiration = get_user_meta( $user_id, 'rcp_expiration', true );
	return $expiration != 'none' ? strtotime( $expiration ) : false;
}

/*
* Gets the status of a user's subscription. If a user is expired, this will update their status to "expired"
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The status of the user's subscription
*/
function rcp_get_status( $user_id ) {
	$status = get_user_meta( $user_id, 'rcp_status', true);

	// double check that the status and expiration match. Update if needed
	if( $status == 'active' && rcp_is_expired( $user_id ) ) {
		rcp_set_status( $user_id, 'expired' );
		$status = 'expired';
	}
	if( $status == '' ) $status = __( 'free', 'rcp' );
	return $status;
}

/*
* Gets a user's subscription status in a nice format that is localized
* @param int $user_id - the ID of the user to return the subscription level of
* return string - The user's subscription status
*/
function rcp_print_status( $user_id ) {
	$status = rcp_get_status( $user_id );
	switch ( $status ) :

		case 'active';
			$print_status = __( 'Active', 'rcp' );
		break;
		case 'expired';
			$print_status = __( 'Expired', 'rcp' );
		break;
		case 'pending';
			$print_status = __( 'Pending', 'rcp' );
		break;
		case 'cancelled';
			$print_status = __( 'Cancelled', 'rcp' );
		break;
		default:
			$print_status = __( 'Free', 'rcp' );
		break;

	endswitch;

	return $print_status;
}

/*
* Sets a user's status to the specified status
* @param int $user_id - the ID of the user to return the subscription level of
* @param string $new_status - the status to set the user to
* return bool - TRUE on a successful status change, false otherwise
*/
function rcp_set_status( $user_id, $new_status) {
	if( update_user_meta( $user_id, 'rcp_status', $new_status ) ) {
		delete_user_meta( $user_id, '_rcp_expired_email_sent');
		do_action( 'rcp_set_status', $new_status, $user_id );
		return true;
	}
	return false;
}

/*
* Gets the user's unique subscription key
* @param int $user_id - the ID of the user to return the subscription level of
* return string/bool - string if the the key is retrieved successfully, false on failure
*/
function rcp_get_subscription_key( $user_id ) {
	$key = get_user_meta( $user_id, 'rcp_subscription_key', true );
	if( $key )
		return $key;
	return false;
}

/*
* Checks whether a user has trialed
* @param int $user_id - the ID of the user to return the subscription level of
* return bool - TRUE if the user has trialed, false otherwise
*/
function rcp_has_used_trial( $user_id = 0) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( get_user_meta( $user_id, 'rcp_has_trialed', true ) == 'yes' ) {
		$ret = true;
	}
	return apply_filters( 'rcp_has_used_trial', $ret, $user_id );
}


/**
 * Checks if a user is currently trialing
 *
 * @access      public
 * @since       1.5
 * @return      bool
 */
function rcp_is_trialing( $user_id = 0 ) {

	$ret = false;

	if( empty( $user_id ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
	}

	if( get_user_meta( $user_id, 'rcp_is_trialing', true ) == 'yes' && rcp_is_active( $user_id ) ) {
		$ret = true;
	}
	return apply_filters( 'rcp_is_trailing', $ret, $user_id );
}


// prints payment history for the specified user
function rcp_print_user_payments( $user_id ) {
	$payments = new RCP_Payments;
	$user_payments = $payments->get_payments( array( 'user_id' => $user_id ) );
	$payments_list = '';
	if( $user_payments ) :
		foreach( $user_payments as $payment ) :
			$payments_list .= '<ul class="rcp_payment_details">';
				$payments_list .= '<li>' . __( 'Date', 'rcp' ) . ': ' . $payment->date . '</li>';
				$payments_list .= '<li>' . __( 'Subscription', 'rcp' ) . ': ' . $payment->subscription . '</li>';
				$payments_list .= '<li>' . __( 'Payment Type', 'rcp' ) . ': ' . $payment->payment_type . '</li>';
				$payments_list .= '<li>' . __( 'Subscription Key', 'rcp' ) . ': ' . $payment->subscription_key . '</li>';
				if( $payment->amount != '' ) {
					$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount ) . '</li>';
				} else {
					$payments_list .= '<li>' . __( 'Amount', 'rcp' ) . ': ' . rcp_currency_filter( $payment->amount2 ) . '</li>';
				}
			$payments_list .= '</ul>';
		endforeach;
	else :
		$payments_list = '<p class="rcp-no-payments">' . __( 'No payments recorded', 'rcp' ) . '</p>';
	endif;
	return $payments_list;
}


// returns the role of the specified user
function rcp_get_user_role( $user_id ) {

	global $wpdb;

	$user = get_userdata( $user_id );
	$capabilities = $user->{$wpdb->prefix . 'capabilities'};

	if ( !isset( $wp_roles ) )
	   $wp_roles = new WP_Roles();

	   foreach ( $wp_roles->role_names as $role => $name ) {

	   if ( array_key_exists( $role, $capabilities ) )
	   return $role;
	}
}


/**
 * Determine if it's possible to upgrade a user's subscription
 *
 * @since       v1.5
 * @access      public
 * @param       $user_id INT the ID of the user to check
 * @return      bool
*/

function rcp_subscription_upgrade_possible( $user_id = 0 ) {

	if( empty( $user_id ) )
		$user_id = get_current_user_id();

	$ret = false;

	if( ! rcp_is_active( $user_id ) && rcp_has_paid_levels() )
		$ret = true;

	return (bool) apply_filters( 'rcp_can_upgrade_subscription', $ret, $user_id );
}



/**
 * Process Profile Updater Form
 *
 * Processes the profile updater form by updating the necessary fields
 *
 * @access      private
 * @since       1.5
*/
function rcp_process_profile_editor_updates() {

	// Profile field change request
	if ( empty( $_POST['rcp_action'] ) || $_POST['rcp_action'] !== 'edit_user_profile' || !is_user_logged_in() )
		return false;


	// Nonce security
	if ( ! wp_verify_nonce( $_POST['rcp_profile_editor_nonce'], 'rcp-profile-editor-nonce' ) )
		return false;

	$user_id = get_current_user_id();

	$display_name = sanitize_text_field( $_POST['rcp_display_name'] );
	$first_name   = sanitize_text_field( $_POST['rcp_first_name'] );
	$last_name    = sanitize_text_field( $_POST['rcp_last_name'] );
	$email        = sanitize_email( $_POST['rcp_email'] );

	$userdata = array(
		'ID'           => $user_id,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => $display_name,
		'user_email'   => $email
	);

	// New password
	if ( ! empty( $_POST['rcp_new_user_pass1'] ) ) {
		if ( $_POST['rcp_new_user_pass1'] !== $_POST['rcp_new_user_pass2'] ) {
			rcp_errors()->add( 'password_mismatch', __( 'The passwords you entered do not match. Please try again.', 'rcp' ) );
		} else {
			$userdata['user_pass'] = $_POST['rcp_new_user_pass1'];
		}
	}

	// retrieve all error messages, if any
	$errors = rcp_errors()->get_error_messages();

	// only create the user if there are no errors
	if( empty( $errors ) ) {

		// Update the user
		$updated = wp_update_user( $userdata );

		if( $updated ) {
			do_action( 'rcp_user_profile_updated', $user_id, $userdata );
			wp_redirect( add_query_arg( 'updated', 'true', $_POST['rcp_redirect'] ) );
			exit;
		} else {
			rcp_errors()->add( 'not_updated', __( 'There was an error updating your profile. Please try again.', 'rcp' ) );
		}
	}
}
add_action( 'init', 'rcp_process_profile_editor_updates' );