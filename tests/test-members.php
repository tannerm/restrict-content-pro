<?php

class RCP_Member_Tests extends WP_UnitTestCase {

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

		rcp_set_status( 1, 'expired' );

		$status = rcp_get_status( 1 );

		$this->assertEquals( 'expired', $status );

	}
}

