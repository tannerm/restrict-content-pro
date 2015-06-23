<?php

class RCP_WooCommerce {
	
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'data_display' ) );
	}

	public function data_tab( $tabs ) {

		$tabs['access'] = array(
			'label'  => __( 'Access Control', 'rcp' ),
			'target' => 'rcp_access_control',
			'class'  => array(),
		);
	
		return $tabs;

	}

	public function data_display() {
?>
		<div id="rcp_access_control" class="panel woocommerce_options_panel">
			
			<div class="options_group">
				<p><?php _e( 'Restrict purchasing of this product to:', 'rcp' ); ?></p>
				
			</div>

			<div class="options_group">
				<p><?php _e( 'Restrict viewing of this product to:', 'rcp' ); ?></p>
			</div>

		</div>
<?php
	}
}
new RCP_WooCommerce;