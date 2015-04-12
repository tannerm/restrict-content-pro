<?php

class RCP_Member_Tests extends WP_UnitTestCase {

	protected $member;

	public function setUp() {
		parent::setUp();
		$this->member = new RCP_Member( 1 );
	}

	function test_get_default_status() {
		$status = rcp_get_status( 1 );
		$this->assertEquals( 'free', $status );
	}

	function test_set_status() {

		rcp_set_status( 1, 'active' );

		$status = rcp_get_status( 1 );

		$this->assertEquals( 'active', $status );

		rcp_set_status( 1, 'pending' );

		$status = rcp_get_status( 1 );

		$this->assertEquals( 'pending', $status );

		rcp_set_status( 1, 'cancelled' );

		$status = rcp_get_status( 1 );

		$this->assertEquals( 'cancelled', $status );

		$this->member->set_status( 'expired' );

		$this->assertEquals( 'expired', $this->member->get_status() );

	}

	function test_get_status() {

		$this->assertEquals( 'free', $this->member->get_status() );
		$this->assertEquals( 'free', rcp_get_status( 1 ) );

	}

	function test_get_expiration_date() {

		// Should be today
		$this->assertEquals( date_i18n( get_option( 'date_format' ) ), $this->member->get_expiration_date() );

		$this->member->set_expiration_date( 'none' );

		$this->assertEquals( 'none', $this->member->get_expiration_date() );

		$this->member->set_expiration_date( '2025-01-01 00:00:00' );

		$this->assertEquals( date_i18n( get_option( 'date_format' ), strtotime( '2025-01-01 00:00:00' ) ), $this->member->get_expiration_date() );
		$this->assertEquals( '2025-01-01 00:00:00', $this->member->get_expiration_date( false) );
	}	
}

