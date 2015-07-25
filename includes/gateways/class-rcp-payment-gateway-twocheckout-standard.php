<?php
/**
 * Payment Gateway Base Class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_Twocheckout_Standard extends RCP_Payment_Gateway {

  /**
   * get things going
   *
   * @since      2.1
   */
  public function init() {
    global $rcp_options;

    $this->supports[]  = 'one-time';
    $this->supports[]  = 'recurring';
    $this->supports[]  = 'fees';

    $this->test_mode   = isset( $rcp_options['sandbox'] );

    if( $this->test_mode ) {

      $this->secret_key      = isset( $rcp_options['twocheckout_test_private'] )      ? trim( $rcp_options['twocheckout_test_private'] )      : '';
      $this->publishable_key = isset( $rcp_options['twocheckout_test_publishable'] ) ? trim( $rcp_options['twocheckout_test_publishable'] ) : '';

    } else {

      $this->secret_key      = isset( $rcp_options['twocheckout_live_private'] )      ? trim( $rcp_options['twocheckout_live_private'] )      : '';
      $this->publishable_key = isset( $rcp_options['twocheckout_live_publishable'] ) ? trim( $rcp_options['twocheckout_live_publishable'] ) : '';

    }

    if( ! class_exists( 'Twocheckout' ) ) {
      require_once RCP_PLUGIN_DIR . 'includes/libraries/twocheckout/Twocheckout.php';
    } 
  } // end init

}
