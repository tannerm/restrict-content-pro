<?php

/**
 * RCP Subscription Levels class
 *
 * This class handles querying, inserting, updating, and removing subscription levels
 * Also includes other discount helper functions
 *
 * @since 1.5
*/

class RCP_Levels {

	/**
	 * Holds the name of our levels database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_name;


	/**
	 * Holds the version number of our levels database table
	 *
	 * @access  private
	 * @since   1.5
	*/

	private $db_version;


	/**
	 * Get things started
	 *
	 * @since   1.5
	*/

	function __construct() {

		$this->db_name    = rcp_get_levels_db_name();
		$this->db_version = '1.2';

	}


	/**
	 * Insert a subscription level into the database
	 *
	 * @access  public
	 * @since   1.5
	*/

	public function insert( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'name'          => '',
			'description'   => '',
			'duration'      => 'unlimited',
			'duration_unit' => 'm',
			'price'         => '0',
			'list_order'    => '0',
			'level' 	    => '0',
			'status'        => 'inactive'
		);

		$args = wp_parse_args( $args, $defaults );

		$add = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->db_name} SET
					`name`          = '%s',
					`description`   = '%s',
					`duration`      = '%d',
					`duration_unit` = '%s',
					`price`         = '%s',
					`list_order`    = '0',
					`level`         = '%d',
					`status`        = '%s'
				;",
				$args['name'],
				$args['description'],
				$args['duration'],
				$args['duration_unit'],
				$args['price'],
				$args['level'],
				$args['status']
			 )
		);

		if( $add ) {
			do_action( 'rcp_add_subscription', $wpdb->insert_id, $args );
			delete_transient( 'rcp_subscription_levels' );
			return $wpdb->insert_id;
		}

		return false;

	}

}