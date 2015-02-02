<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
*/

class RCP_Payment_Gateway_Stripe extends RCP_Payment_Gateway {

	public $id;

	public function init() {

		global $rcp_options;

		$this->id          = 'stripe';
		$this->title       = 'Stripe';
		$this->description = __( 'Pay with a credit or debit card', 'rcp' );
		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';

		$this->test_mode   = isset( $rcp_options['sandbox'] );

		add_action( 'init', array( $this, 'process_webhooks' ) );

	}

	public function process_signup() {

		

	}

	public function process_webhooks() {
		
		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'stripe' ) {
			return;
		}

		global $rcp_options;

		

	}

	public function fields() {
		
		ob_start();
		rcp_get_template_part( 'card-form' );
		return ob_get_clean();
	}

}