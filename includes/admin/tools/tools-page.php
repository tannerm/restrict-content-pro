<?php
/**
 * Displays the Tools page
 */
function rcp_sysinfo_page() {
	if( ! current_user_can( 'rcp_view_payments' ) ) {
		return;
	}

	include RCP_PLUGIN_DIR . 'includes/admin/tools/system-info.php';

	if ( isset( $_POST['rcp-sysinfo'] ) ) {
		rcp_tools_sysinfo_download();
	}

?>

	<div class="wrap">
		<h1><?php _e( 'Restrict Content Pro Tools', 'rcp' ); ?></h1>

		<form action="<?php echo esc_url( admin_url( 'admin.php?page=rcp-tools' ) ); ?>" method="post" dir="ltr">
			<textarea readonly="readonly" onclick="this.focus(); this.select()" id="rcp-system-info-textarea" name="rcp-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo rcp_tools_system_info_report(); ?></textarea>
			<p class="submit">
				<input type="hidden" name="rcp-action" value="download_sysinfo" />
				<?php submit_button( 'Download System Info File', 'primary', 'rcp-download-sysinfo', false ); ?>
			</p>
		</form>
	</div>
<?php
}