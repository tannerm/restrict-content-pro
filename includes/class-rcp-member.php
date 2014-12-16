<?php
/**
 * RCP Member class
 *
 * @since 2.1
*/

class RCP_Member extends WP_User {

	public function get_status() {

		$status = get_user_meta( $this->ID, 'rcp_status', true);

		// double check that the status and expiration match. Update if needed
		if( $status == 'active' && rcp_is_expired( $this->ID ) ) {

			$status = 'expired';
			$this->set_status( $status );

		}

		if( empty( $status ) ) {
			$status = 'free';
		}

		return apply_filters( 'rcp_member_get_status', $status, $this->ID, $this );

	}

	public function set_status( $new_status = '' ) {

		$ret        = false;
		$old_status = $this->get_status();

		if( $old_status != $new_status ) {

			if( update_user_meta( $this->ID, 'rcp_status', $new_status ) ) {

				if( 'expired' != $new_status ) {
					delete_user_meta( $this->ID, '_rcp_expired_email_sent');
				}

				do_action( 'rcp_set_status', $new_status, $this->ID );

				// Record the status change
				rcp_add_member_note( $this->ID, sprintf( __( 'Member\'s status changed from %s to %s', 'rcp' ), $old_status, $new_status ) );

				$ret = true;
			}

		}

		return $ret;

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

		return apply_filters( 'rcp_member_get_expiration_time', strtotime( $expiration ), $this->ID, $this );

	}

	public function set_expiration_date( $date = '' ) {

		$ret      = false;
		$old_date = $this->get_expiration_date();

		if( $old_date !== $new_date ) {
			
			if( update_user_meta( $this->ID, 'rcp_expiration', $new_date ) ) {


				// Record the status change
				$note = sprintf( __( 'Member\'s expiration changed from %s to %s', 'rcp' ), $old_date, $new_date );
				rcp_add_member_note( $this->ID, $note );

			}

			do_action( 'rcp_set_expiration_date', $this->ID, $new_date, $old_date );
		
			$ret = true;
		}

		return $ret;

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

		$notes = get_user_meta( $this->ID, 'rcp_notes', true );

		return apply_filters( 'rcp_member_get_notes', $notes, $this->ID, $this );

	}

	public function add_note( $note = '' ) {

		$notes = $this->get_notes();

		if( empty( $notes ) ) {
			$notes = '';
		}

		$note = apply_filters( 'rcp_member_pre_add_note', $note, $this->ID, $this );

		$notes .= "\n\n" . date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;

		update_user_meta( $this->ID, 'rcp_notes', wp_kses( $notes, array() ) );

		do_action( 'rcp_member_add_note', $note, $this->ID, $this );

		return true;

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