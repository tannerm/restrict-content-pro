<?php
/**
 * PayPal Express Gateway class
 *
 * @package     Restrict Content Pro
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal_Express extends RCP_Payment_Gateway {

	public $id;

	public function init() {

		global $rcp_options;

		$this->id          = 'paypal_express';
		$this->title       = 'PayPal Express';
		$this->description = 'It is PayPal, what else?';
		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->test_mode   = isset( $rcp_options['sandbox'] );

	}

	public function process_signup() {

		

	}

	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'EIPN' ) {
			return;
		}

	}

}