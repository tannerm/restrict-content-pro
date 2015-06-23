<?php

class RCP_Integrations {

	public function __construct() {

		$this->load();

	}

	public function get_integrations() {

		return apply_filters( 'rcp_integrations', array(
			'woocommerce'    => 'WooCommerce',
		) );

	}

	public function load() {

		do_action( 'rcp_integrations_load' );

		foreach( $this->get_integrations() as $filename => $integration ) {

			if( file_exists( RCP_PLUGIN_DIR . 'includes/integrations/class-rcp-' . $filename . '.php' ) ) {
				require_once RCP_PLUGIN_DIR . 'includes/integrations/class-rcp-' . $filename . '.php';
			}

		}

		do_action( 'rcp_integrations_loaded' );

	}

}
new RCP_Integrations;