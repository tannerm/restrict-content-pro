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

		$expiration = get_user_meta( $this->ID, 'rcp_expiration', true );

		if( $expiration ) {
			$expiration = $expiration != 'none' ? date_i18n( get_option( 'date_format' ), strtotime( $expiration ) ) : 'none';
		}

		return apply_filters( 'rcp_member_get_expiration_date', $expiration, $this->ID, $this );

	}

	public function get_expiration_time() {

		$expiration = get_user_meta( $this->ID, 'rcp_expiration', true );

		return apply_filters( 'rcp_member_get_expiration_time', $expiration, $this->ID, $this );

	}

	public function set_expiration_date( $date = '' ) {

	}

	public function renew() {
		
	}

	public function get_subscription_id() {

		$subscription_id = get_user_meta( $this->ID, 'rcp_subscription_level', true );

		return apply_filters( 'rcp_member_get_subscription_id', $subscription_id, $this->ID, $this );

	}

	public function get_subscription_key() {

		$subscription_key = get_user_meta( $this->ID, 'rcp_subscription_key', true );

		return apply_filters( 'rcp_member_get_subscription_key', $subscription_key, $this->ID, $this );

	}

	public function get_subscription_name() {

		$subscription_name = $this->get_subscription_id();

		return apply_filters( 'rcp_member_get_subscription_name', $subscription_name, $this->ID, $this );

	}

	public function get_payments() {

	}

	public function get_notes() {

	}

	public function add_note( $note = '' ) {

	}

	public function is_recurring() {

		$ret       = false;
		$recurring = get_user_meta( $this->ID, 'rcp_recurring', true );
		
		if( $recurring == 'yes' ) {
			$ret = true;
		}

		return apply_filters( 'rcp_member_is_recurring', $ret, $this->ID, $this );

	}

	public function is_expired() {

		$ret        = false;
		$expiration = get_user_meta( $this->ID, 'rcp_expiration', true );
		
		if( $expiration && strtotime( 'NOW' ) > strtotime( $expiration ) ) {
			$ret = true;
		}

		if( $expiration == 'none' ) {
			$ret = false;
		}

		return apply_filters( 'rcp_member_is_expired', $ret, $this->ID, $this );

	}

	public function is_trialing() {

		$ret      = false;
		$trialing = get_user_meta( $this->ID, 'rcp_is_trialing', true );


		if( $trialing == 'yes' && rcp_is_active( $this->ID ) ) {
			$ret = true;
		}

		// Old filter for backwards compatibility
		$ret = apply_filters( 'rcp_is_trialing', $ret, $this->ID );

		return apply_filters( 'rcp_member_is_trialing', $ret, $this->ID, $this );


	}

}