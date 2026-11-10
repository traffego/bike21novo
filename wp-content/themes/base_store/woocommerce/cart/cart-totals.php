<?php
/**
 * Resumo e Totais do Carrinho com simulação de PIX à vista e parcelamento configurável.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.6
 */

defined( 'ABSPATH' ) || exit;

$cart_total_num = WC()->cart ? (float) WC()->cart->get_total( 'edit' ) : 0.0;
$pricing        = store_calculate_pricing_summary( $cart_total_num );
$trust_pay      = store_term( 'payment_gateway_label' );
$trust_prep     = store_term( 'prep_badge_label' );
$trust_sec      = store_term( 'security_badge' );
?>
<div class="cart_totals ds-cart-totals-card <?php echo ( WC()->customer && WC()->customer->has_calculated_shipping() ) ? 'calculated_shipping' : ''; ?>">

	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<h2>Resumo do Pedido</h2>

	<table cellspacing="0" class="shop_table shop_table_responsive ds-summary-table">
		<tbody>
			<tr class="cart-subtotal">
				<th>Subtotal</th>
				<td data-title="Subtotal"><?php wc_cart_totals_subtotal_html(); ?></td>
			</tr>

			<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
				<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
					<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
					<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

				<?php do_action( 'woocommerce_cart_totals_before_shipping' ); ?>

				<?php wc_cart_totals_shipping_html(); ?>

				<?php do_action( 'woocommerce_cart_totals_after_shipping' ); ?>

			<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>

				<tr class="shipping">
					<th>Entrega</th>
					<td data-title="Entrega"><?php woocommerce_shipping_calculator(); ?></td>
				</tr>

			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
				<tr class="fee">
					<th><?php echo esc_html( $fee->name ); ?></th>
					<td data-title="<?php echo esc_attr( $fee->name ); ?>"><?php wc_cart_totals_fee_html( $fee ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php
			if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
				$taxable_address = WC()->customer->get_taxable_address();
				$estimated_text  = '';

				if ( WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping() ) {
					$estimated_text = sprintf( ' <small>(estimado para %s)</small>', WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] );
				}

				if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
					foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
						?>
						<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
							<th><?php echo esc_html( $tax->label ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
							<td data-title="<?php echo esc_attr( $tax->label ); ?>"><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
						</tr>
						<?php
					}
				} else {
					?>
					<tr class="tax-total">
						<th><?php echo esc_html( WC()->countries->tax_or_vat() ) . $estimated_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></th>
						<td data-title="<?php echo esc_attr( WC()->countries->tax_or_vat() ); ?>"><?php wc_cart_totals_taxes_total_html(); ?></td>
					</tr>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

			<tr class="order-total">
				<th>Total</th>
				<td data-title="Total"><?php wc_cart_totals_order_total_html(); ?></td>
			</tr>

			<?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>
		</tbody>
	</table>

	<?php if ( $cart_total_num > 0 && $pricing['installments_count'] > 1 ) : ?>
		<p class="ds-cart-installment-note">
			ou em até <strong><?php echo esc_html( $pricing['installments_count'] ); ?>x de <?php echo esc_html( store_format_brl( $pricing['installment_value'] ) ); ?></strong>
			<?php echo esc_html( $pricing['installments_label'] ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $cart_total_num > 0 && $pricing['cash_percent'] > 0 && $pricing['cash_price'] > 0 ) : ?>
		<div class="ds-pix-highlight-box">
			<div class="ds-pix-highlight-box__row">
				<span>À vista no PIX (<?php echo esc_html( $pricing['cash_percent'] ); ?>% OFF)</span>
				<strong class="ds-pix-highlight-box__val"><?php echo esc_html( store_format_brl( $pricing['cash_price'] ) ); ?></strong>
			</div>
			<?php if ( $pricing['cash_savings'] > 0 ) : ?>
				<span class="ds-pix-highlight-box__savings">
					Economia imediata de <?php echo esc_html( store_format_brl( $pricing['cash_savings'] ) ); ?> ao escolher PIX no checkout.
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="wc-proceed-to-checkout">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php if ( '' !== $trust_pay || '' !== $trust_prep || '' !== $trust_sec ) : ?>
		<div class="ds-trust-list">
			<?php foreach ( array_filter( array( $trust_pay, $trust_prep, $trust_sec ) ) as $trust_item ) : ?>
				<div class="ds-trust-list__item">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
					<span><?php echo esc_html( $trust_item ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>

</div>
