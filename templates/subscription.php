<?php

if( rcp_is_recurring( $user_ID ) && ! rcp_is_expired( $user_ID ) ) {
	$date_text = __( 'Renewal Date', 'rcp' );
} else {
	$date_text = __( 'Expiration Date', 'rcp' );
}

$details = '<ul id="rcp_subscription_details">';
	$details .= '<li><span class="rcp_subscription_name">' . __( 'Subscription Level', 'rcp' ) . '</span><span class="rcp_sub_details_separator">:&nbsp;</span><span class="rcp_sub_details_current_level">' . rcp_get_subscription( $user_ID ) . '</span></li>';
	if( rcp_get_expiration_date( $user_ID ) ) {
		$details .= '<li><span class="rcp_sub_details_exp">' . $date_text . '</span><span class="rcp_sub_details_separator">:&nbsp;</span><span class="rcp_sub_details_exp_date">' . rcp_get_expiration_date( $user_ID ) . '</span></li>';
	}
	$details .= '<li><span class="rcp_sub_details_recurring">' . __( 'Recurring', 'rcp' ) . '</span><span class="rcp_sub_details_separator">:&nbsp;</span><span class="rcp_sub_is_recurring">';
	$details .= rcp_is_recurring( $user_ID ) ? __( 'yes', 'rcp' ) : __( 'no', 'rcp' ) . '</span></li>';
	$details .= '<li><span class="rcp_sub_details_status">' . __( 'Current Status', 'rcp' ) . '</span><span class="rcp_sub_details_separator">:&nbsp;</span><span class="rcp_sub_details_current_status">' . rcp_print_status( $user_ID ) . '</span></li>';
	if( ( rcp_is_expired( $user_ID ) || rcp_get_status( $user_ID ) == 'cancelled' ) && rcp_subscription_upgrade_possible( $user_ID ) ) {
		$details .= '<li><a href="' . esc_url( get_permalink( $rcp_options['registration_page'] ) ) . '" title="' . __( 'Renew your subscription', 'rcp' ) . '" class="rcp_sub_details_renew">' . __( 'Renew your subscription', 'rcp' ) . '</a></li>';
	} elseif( !rcp_is_active( $user_ID ) && rcp_subscription_upgrade_possible( $user_ID ) ) {
		$details .= '<li><a href="' . esc_url( get_permalink( $rcp_options['registration_page'] ) ) . '" title="' . __( 'Upgrade your subscription', 'rcp' ) . '" class="rcp_sub_details_renew">' . __( 'Upgrade your subscription', 'rcp' ) . '</a></li>';
	} elseif( rcp_is_active( $user_ID ) && get_user_meta( $user_ID, 'rcp_paypal_subscriber', true) ) {
		$details .= '<li class="rcp_cancel"><a href="https://www.paypal.com/cgi-bin/customerprofileweb?cmd=_manage-paylist" target="_blank" title="' . __( 'Cancel your subscription', 'rcp' ) . '">' . __( 'Cancel your subscription', 'rcp' ) . '</a></li>';
	}
	$details = apply_filters( 'rcp_subscription_details_list', $details );
$details .= '</ul>';
$details .= '<div class="rcp-payment-history">';
	$details .= '<h3 class="payment_history_header">' . __( 'Your Payment History', 'rcp' ) . '</h3>';
	$details .= rcp_print_user_payments( $user_ID );
$details .= '</div>';
$details = apply_filters( 'rcp_subscription_details', $details );

?>
<table class="rcp-table" id="rcp-payment-history">
	<thead>
		<th><?php _e( 'Invoice #', 'rcp' ); ?></th>
		<th><?php _e( 'Subscription', 'rcp' ); ?></th>
		<th><?php _e( 'Payment Method', 'rcp' ); ?></th>
		<th><?php _e( 'Amount', 'rcp' ); ?></th>
		<th><?php _e( 'Date', 'rcp' ); ?></th>
		<th><?php _e( 'Actions', 'rcp' ); ?></th>
	</thead>
	<tbody>
	<?php foreach( rcp_get_user_payments() as $payment ) : ?>
		<tr>
			<td><?php echo $payment->id; ?></td>
			<td><?php echo $payment->subscription; ?></td>
			<td><?php echo $payment->payment_type; ?></td>
			<td><?php echo rcp_currency_filter( $payment->amount ); ?></td>
			<td><?php echo date_i18n( get_option( 'date_format', strtotime( $payment->date ) ) ); ?></td>
			<td><a href="<?php echo rcp_get_pdf_download_url( $payment->id ); ?>"><?php _e( 'PDF Receipt', 'rcp' ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>