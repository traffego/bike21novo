<?php
/**
 * Tabela de Revisão do Pedido no Checkout com miniatura, variação e prévia de pagamento.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.2
 */

defined( 'ABSPATH' ) || exit;

$chosen_pm    = store_resolve_chosen_payment_method();
$is_pix_pm    = store_is_pix_gateway( $chosen_pm );
$cart_total   = WC()->cart ? (float) WC()->cart->get_total( 'edit' ) : 0.0;
$pricing_note = store_calculate_pricing_summary( $cart_total );
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name">Produto</th>
			<th class="product-total">Subtotal</th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$visible  = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
				$cart_item_name = WC()->cart->get_item_product_name( $cart_item, $_product );
				$product_name   = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
				$thumb_html     = $_product->get_image( 'woocommerce_gallery_thumbnail' );
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<td class="product-name">
						<div class="ds-review-item">
							<div class="ds-review-item__thumb">
								<?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="ds-review-item__info">
								<div class="ds-review-item__title">
									<?php echo wp_kses_post( $product_name ); ?>
									<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity ds-review-item__qty">' . sprintf( '&times;&nbsp;%s', esc_html( $cart_item['quantity'] ) ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<?php echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>
		<tr class="cart-subtotal">
			<th>Subtotal</th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th>Total</th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php if ( $cart_total > 0 ) : ?>
			<tr class="ds-installment-note-row">
				<td colspan="2">
					<?php if ( ! $is_pix_pm && $pricing_note['cash_percent'] > 0 && $pricing_note['cash_price'] > 0 ) : ?>
						<div class="ds-pix-highlight-box" style="margin-top:6px;">
							<div class="ds-pix-highlight-box__row">
								<span>Ou no PIX à vista (-<?php echo esc_html( $pricing_note['cash_percent'] ); ?>%)</span>
								<strong class="ds-pix-highlight-box__val"><?php echo esc_html( store_format_brl( $pricing_note['cash_price'] ) ); ?></strong>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! $is_pix_pm && $pricing_note['installments_count'] > 1 ) : ?>
						<p class="ds-cart-installment-note" style="margin-top:8px;">
							Em até <strong><?php echo esc_html( $pricing_note['installments_count'] ); ?>x de <?php echo esc_html( store_format_brl( $pricing_note['installment_value'] ) ); ?></strong>
							<?php echo esc_html( $pricing_note['installments_label'] ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>
