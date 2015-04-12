<?php

class RCP_Member_Tests extends WP_UnitTestCase {

	protected $member;
	protected $level_id;

	public function setUp() {
		parent::setUp();
		$this->member = new RCP_Member( 1 );

		$levels = new RCP_Levels;
		$this->level_id = $levels->insert( array(
			'name'          => 'Gold',
			'duration'      => 1,
			'duration_unit' => 'month',
			'status'        => 'active'
		) );
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

		$this->assertInternalType( 'string', $this->member->get_expiration_date() );

		// Should be today
		$this->assertEquals( date_i18n( get_option( 'date_format' ) ), $this->member->get_expiration_date() );

		$this->member->set_expiration_date( 'none' );

		$this->assertEquals( 'none', $this->member->get_expiration_date() );

		$this->member->set_expiration_date( '2025-01-01 00:00:00' );

		$this->assertEquals( date_i18n( get_option( 'date_format' ), strtotime( '2025-01-01 00:00:00' ) ), $this->member->get_expiration_date() );
		$this->assertEquals( '2025-01-01 00:00:00', $this->member->get_expiration_date( false) );
	}

	function test_get_expiration_time() {

		$this->assertFalse( $this->member->get_expiration_time() );
		
		$this->member->set_expiration_date( date( 'Y-n-d' ) );
		
		$this->assertInternalType( 'int', $this->member->get_expiration_time() );

	}

	function test_set_expiration_date() {
		
		$this->member->set_expiration_date( '2025-01-01 00:00:00' );

		$this->assertEquals( date_i18n( get_option( 'date_format' ), strtotime( '2025-01-01 00:00:00' ) ), $this->member->get_expiration_date() );
		$this->assertEquals( '2025-01-01 00:00:00', $this->member->get_expiration_date( false) );

	}

	function test_is_expired() {

		$this->assertFalse( $this->member->is_expired() );

		$this->member->set_expiration_date( '2014-01-01 00:00:00' );
		$this->assertTrue( $this->member->is_expired() );

		$this->member->set_expiration_date( '2025-01-01 00:00:00' );
		$this->assertFalse( $this->member->is_expired() );

	}

	function test_renew() {

		$this->member->set_expiration_date( '2014-01-01 00:00:00' );
		$this->assertTrue( $this->member->is_expired() );

		// Should be false when no subscription ID is set
		$this->assertFalse( $this->member->renew() );

		update_user_meta( $this->member->ID, 'rcp_subscription_level', $this->level_id );

		delete_user_meta( $this->member->ID, 'rcp_expiration' );

		$this->member->renew();

		$this->assertFalse( $this->member->is_expired() );
		$this->assertEquals( date_i18n( get_option( 'date_format' ), strtotime( '+1 month' ) ), $this->member->get_expiration_date() );

	}

	function test_cancel() {

		$this->assertTrue( $this->member->is_active() );

		$this->member->set_recurring( true );

		$this->member->cancel();

		// Should still be active since the expiration date is in the future
		$this->assertTrue( $this->member->is_active() );
		$this->assertFalse( $this->member->is_recurring() );
		$this->assertEquals( 'cancelled', $this->member->get_status() );
	}
}

