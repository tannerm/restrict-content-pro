<?php

class RCP_Discount_Tests extends WP_UnitTestCase {

	function test_has_discounts() {
		$this->assertFalse( rcp_has_discounts() );
	}

	function test_insert_discount() {

		$discounts_db = new RCP_Discounts;

		$args = array(
			'name'   => 'Test Code',
			'code'   => 'test',
			'status' => 'active',
			'amount' => '10'
		);
		$discount_id = $discounts_db->insert( $args );

		$this->assertGreaterThan( 0, $discount_id );

	}

}

