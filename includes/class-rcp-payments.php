<?php

/**
 * RCP Payments class
 *
 * This class handles querying, inserting, updating, and removing payments
 * Also handles calculating earnings
 *
 * @since 1.5
*/

class RCP_Payments {


	/**
	 * Holds the name of our payments database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_name;


	/**
	 * Holds the version number of our discounts database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_version;


	function __construct() {

		$this->db_name    = rcp_get_payments_db_name();
		$this->db_version = '1.2';

	}


	/**
	 * Add a payment to the database
	 *
	 * @access  public
	 * @param   $payment_data Array All of the payment data, such as amount, date, user ID, etc
	 * @since   1.5
	*/

	public function insert( $payment_data = array() ) {

		global $wpdb;

		$defaults = array(
			'subscription' 		=> 0,
			'date' 				=> date( 'Y-m-d H:i:s' ),
			'amount' 			=> 0.00,
			'user_id' 			=> 0,
			'payment_type' 		=> '',
			'subscription_key' 	=> ''
		);

		$args = wp_parse_args( $payment_data, $defaults );

		if( $this->payment_exists( $args ) )
			return;

		$wpdb->insert( $this->db_name, $args, array( '%s', '%s', '%s', '%d', '%s', '%s' ) );

		// if insert was succesful, return the payment ID
		if( $wpdb->insert_id ) {
			// clear the payment caches
			delete_transient( 'rcp_payments' );
			delete_transient( 'rcp_earnings' );
			delete_transient( 'rcp_payments_count' );
			do_action( 'rcp_insert_payment', $wpdb->insert_id, $args, $amount );
			return $wpdb->insert_id;
		}

		return false;

	}


	/**
	 * Checks if a payment exists in the DB
	 *
	 * @access  public
	 * @param   $args Array An array of the payment details we need to look for
	 * @since   1.5
	*/

	public function payment_exists( $args = array() ) {

		global $wpdb;

		$found = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM " . $this->db_name . " WHERE `date`='%s' AND `subscription_key`='%s' AND `payment_type`='%s';",
				$args['date'],
				$args['subscription_key'],
				$args['payment_type']
			)
		);

		if( $found )
			return true; // this payment already exists

		return false;

	}


	/**
	 * Update a payment in the datbase.
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function update( $payment_id = 0, $payment_data = array() ) {

		global $wpdb;

		// TODO

	}


	/**
	 * Delete a payment from the datbase.
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function delete( $payment_id = 0 ) {
		global $wpdb;
		do_action( 'rcp_delete_payment', $payment_id );
		$remove = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->db_name} WHERE `id` = '%d';", absint( $payment_id ) ) );

	}


	/**
	 * Retrieve a specific payment
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_payment( $payment_id = 0 ) {

		global $wpdb;

		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT FROM {$this->db_name} WHERE id = %d", absint( $payment_id ) ) );

		return $payment;

	}


	/**
	 * Retrieve payments from the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_payments( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'  => 20,
			'offset'  => 0,
			'user_id' => 0,
			'date'    => array()
		);

		$args  = wp_parse_args( $args, $defaults );

		$where = '';

		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			$where .= "WHERE `user_id` IN( {$user_ids} ) ";

		}

		// Setup the date query
		if( ! empty( $args['date'] ) && is_array( $args['date'] ) ) {

			$day   = ! empty( $args['date']['day'] )   ? absint( $args['date']['day'] )   : null;
			$month = ! empty( $args['date']['month'] ) ? absint( $args['date']['month'] ) : null;
			$year  = ! empty( $args['date']['year'] )  ? absint( $args['date']['year'] )  : null;
			$date_where = '';

			$date_where .= ! is_null( $year )  ? $year  . " = YEAR ( date ) "                    : '';

			if( ! is_null( $month ) ) {
				$date_where = $month  . " = MONTH ( date ) AND " . $date_where;
			}

			if( ! is_null( $day ) ) {
				$date_where = $day . " = DAY ( date ) AND " . $date_where;
			}

			if( ! empty( $args['user_id'] ) ) {
				$where .= "AND (" . $date_where . ")";
			} else {
				$where .= "WHERE ( " . $date_where . " ) ";
			}
		}

		if( $args['number'] > 0 ) {

			$payments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $this->db_name . " {$where}ORDER BY id DESC LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ) );

		} else {

			// when retrieving all payments, the query is cached
			$payments = get_transient( 'rcp_payments' );

			if( $payments === false ) {
				$payments = $wpdb->get_results( "SELECT * FROM " . $wpdb->escape( $rcp_payments_db_name ) . " {$where}ORDER BY id DESC;" ); // this is to get all payments
				set_transient( 'rcp_payments', $payments, 10800 );
			}

		}

		return $payments;

	}


	/**
	 * Count the total number of payments in the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'user_id' => 0
		);

		$args  = wp_parse_args( $args, $defaults );

		$where = '';

		if( ! empty( $args['user_id'] ) ) {

			if( is_array( $args['user_id'] ) )
				$user_ids = implode( ',', $args['user_id'] );
			else
				$user_ids = intval( $args['user_id'] );

			$where .= " WHERE `user_id` IN( {$user_ids} ) ";

		}

		$key   = md5( 'rcp_payments_' . serialize( $args ) );
		$count = get_transient( $key );

		if( $count === false ) {
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM " . $this->db_name . "{$where};" );
			set_transient( $key, $count, 10800 );
		}

		return $count;

	}


	/**
	 * Calculate the total earnings of all payments in the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function get_earnings( $args = array() ) {

		global $wpdb;

		$payments = get_transient( 'rcp_earnings' );

		if( $payments === false ) {

			$payments = $wpdb->get_results( "SELECT amount FROM " . $this->db_name . ";" );
			// cache the earnings amoung
			set_transient( 'rcp_earnings', $payments, 10800 );

		}

		$total = (float) 0.00;

		if( $payments ) :
			foreach( $payments as $payment ) :
				$total = $total + $payment->amount;
			endforeach;
		endif;

		return $total;

	}


	/**
	 * Retrieves the last payment made by a user
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function last_payment_of_user( $user_id = 0 ) {
		global $wpdb;
		$query = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $this->db_name . " WHERE `user_id`='%d' ORDER BY id DESC LIMIT 1;", $user_id ) );
		if( $query )
			return $query[0]->amount;
		return false;
	}

}