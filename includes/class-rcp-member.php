<?php
/**
 * RCP Member class
 *
 * @since 2.1
*/

class RCP_Member extends WP_User {

	public function get_status() {

	}

	public function set_status( $new_status = '' ) {

	}

	public function get_expiration_date() {

	}

	public function get_expiration_time() {

	}

	public function set_expiration_date( $date = '' ) {

	}

	public function renew() {
		
	}

	public function get_subscription_id() {

		$subscription_id = get_user_meta( $this->ID, 'rcp_subscription_level', true );

		return apply_filters( 'rcp_member_get_subscription_id', $subscription_id, $this->ID );

	}

	public function get_subscription_key() {

	}

	public function get_subscription_name() {

		$subscription_name = $this->get_subscription_id();

		return apply_filters( 'rcp_member_get_subscription_name', $subscription_name, $this->ID );

	}

	public function get_payments() {

	}

	public function get_notes() {

	}

	public function add_note( $note = '' ) {

	}

	public function is_recurring() {

	}

	public function is_expired() {

	}

	public function is_trialing() {

	}

}