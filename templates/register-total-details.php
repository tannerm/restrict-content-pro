<?php
global $rcp_cart;

if ( ! is_a( $rcp_cart, 'RCP_Cart' ) ) {
	return;
} ?>

<table class="rcp_registration_total_details">

	<tbody style="vertical-align: top;">
		<?php if ( rcp_get_subscription_price( $rcp_cart->get_subscription() ) ) : ?>
			<tr>
				<th><?php _e( 'Subscription', 'rcp' ); ?></th>
				<th><?php _e( 'Description', 'rcp' ); ?></th>
				<th><?php _e( 'Amount', 'rcp' ); ?></th>
			</tr>

			<tr>
				<td><?php echo rcp_get_subscription_name( $rcp_cart->get_subscription() ); ?></td>
				<td><?php echo wpautop( wptexturize( rcp_get_subscription_description( $rcp_cart->get_subscription() ) ) ); ?></td>
				<td><?php echo rcp_currency_filter( rcp_get_subscription_price( $rcp_cart->get_subscription() ) ); ?></td>
			</tr>

			<?php if ( $rcp_cart->get_fees() || $rcp_cart->get_discounts() ) : ?>
				<tr>
					<th colspan="3"><?php _e( 'Discounts and Fees', 'rcp' ); ?></th>
				</tr>

				<?php // Discounts ?>
				<?php if ( $rcp_cart->get_discounts() ) : foreach( $rcp_cart->get_discounts() as $code => $recuring ) : if ( ! $discount = rcp_get_discount_details_by_code( $code ) ) continue; ?>
					<tr>
						<td><?php echo esc_html( $discount->name ); ?></td>
						<td><?php echo esc_html( $discount->description ); ?></td>
						<td><?php echo esc_html( rcp_discount_sign_filter( $discount->amount, $discount->unit ) ); ?></td>
					</tr>
				<?php endforeach; endif; ?>

				<?php // Fees ?>
				<?php if ( $rcp_cart->get_fees() ) : foreach( $rcp_cart->get_fees() as $fee ) : ?>
					<?php
					$amount = ( $fee['amount'] < 0 ) ? '-' : '' ;
					$amount .= rcp_currency_filter( abs( $fee['amount'] ) )
					?>
					<tr>
						<td colspan="2"><?php echo esc_html( $fee['description'] ); ?></td>
						<td><?php echo esc_html( $amount ); ?></td>
					</tr>
				<?php endforeach; endif; ?>

			<?php endif; ?>
		<?php endif; ?>

		<tr>
			<th colspan="2"><?php _e( 'Total Today', 'rcp' ); ?></th>
			<th><?php rcp_registration_total(); ?></th>
		</tr>

		<tr>
			<th colspan="2"><?php _e( 'Recurring Total', 'rcp' ); ?></th>
			<th><?php rcp_registration_recurring_total(); ?></th>
		</tr>

	</tbody>
</table>