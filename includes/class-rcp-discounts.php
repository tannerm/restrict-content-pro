<?php

/**
 * RCP Discounts class
 *
 * This class handles querying, inserting, updating, and removing discounts
 * Also includes other discount helper functions
 *
 * @since 1.5
*/


class RCP_Discounts {

	/**
	 * Holds the name of our discounts database table
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

		$this->db_name    = rcp_get_discounts_db_name();
		$this->db_version = '1.1';

	}


	public function get_discounts( $args = array() ) {
		global $wpdb;

		// TODO: Add optional args for limit, order, etc

		$discounts = $wpdb->get_results( "SELECT * FROM {$this->db_name};" );

		if( $discounts )
			return $discounts;
		return false;

	}


	public function get_discount( $discount_id = 0 ) {
		global $wpdb;

		$discount = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE id='%d';", $discount_id ) );

		return $discount;

	}

	public function get_by( $field = 'code', $value = '' ) {
		global $wpdb;

		$discount = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->db_name} WHERE {$field}='%s';", $value ) );

		return $discount;

	}


	public function get_status( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->status;
		return false;

	}

	public function get_amount( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->amount;
		return 0;

	}


	public function get_uses( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->use_count;
		return 0;

	}

	public function get_max_uses( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->max_uses;
		return 0;

	}

	public function get_expiration( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->expiration;
		return false;

	}

	public function get_type( $discount_id = 0 ) {

		$discount = $this->get_discount( $discount_id );

		if( $discount )
			return $discount->unit;
		return false;

	}


	public function insert( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'name'        => '',
			'description' => '',
			'amount'      => '0.00',
			'status'      => 'inactive',
			'unit'        => '%',
			'code'        => '',
			'expiration'  => '',
			'max_uses' 	  => 0,
			'use_count'   => '0'
		);

		$args = wp_parse_args( $payment_data, $defaults );

		do_action( 'rcp_pre_add_discount', $args );

		$add = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->db_name} SET
					`name`        = '%s',
					`description` = '%s',
					`amount`      = '%s',
					`status`      = 'active',
					`unit`        = '%s',
					`code`        = '%s',
					`expiration`  = '%s',
					`max_uses`    = '%d',
					`use_count`   = '0'
				;",
				anitize_text_field( $args['name'] ),
				strip_tags( addslashes( $args['description'] ) ),
				anitize_text_field( $args['amount'] ),
				$args['unit'],
				sanitize_text_field( $args['code'] ),
				anitize_text_field( $args['expiration'] ),
				absint( $args['max_uses'] )
			)
		);

		do_action( 'rcp_add_discount', $args, $wpdb->insert_id );

		if( $add )
			return $wpdb->insert_id;
		return false;
	}

	public function update( $discount_id = 0, $args = array() ) {

		global $wpdb;

		$defaults = array(
			'id'          => 0,
			'name'        => '',
			'description' => '',
			'amount'      => '0.00',
			'status'      => 'inactive',
			'unit'        => '%',
			'code'        => '',
			'expiration'  => '',
			'max_uses' 	  => 0,
			'use_count'   => '0'
		);

		$args = wp_parse_args( $payment_data, $defaults );

		do_action( 'rcp_pre_edit_discount', absint( $args['id'] ), $args );

		$update = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->db_name} SET
					`name`        = '%s',
					`description` = '%s',
					`amount`      = '%s',
					`unit`        = '%s',
					`code`        = '%s',
					`status`      = '%s',
					`expiration`  = '%s',
					`max_uses`    = '%s'
					WHERE `id`    = '%d'
				;",
				sanitize_text_field( $args['name'] ),
				strip_tags( addslashes( $args['description'] ) ),
				sanitize_text_field( $args['amount'] ),
				$unit,
				sanitize_text_field( $args['code'] ),
				sanitize_text_field( $args['status'] ),
				sanitize_text_field( $args['expiration'] ),
				sanitize_text_field( $args['max_use'] ),
				absint( $args['id'] )
			)
		);

		do_action( 'rcp_edit_discount', absint( $args['id'] ), $args );

	}

	public function delete( $discount_id = 0 ) {

	}

	public function is_maxed_out( $discount_id = 0 ) {

	}

	public function is_expired( $discount_id = 0 ) {

	}

	public function add_to_user( $user_id = 0, $discount_code = '' ) {

	}

	public function user_has_used( $user_id = 0, $discount_code = '' ) {

	}

	public function increase_use( $discount_id = 0 ) {

	}

	public function count_uses( $discount_id = 0 ) {

	}

	public function format_discount( $amount = '', $type = '' ) {

	}

	public function calc_discounted_price( $base_price = '', $discount_amount = '', $type = '%' ) {

	}

}