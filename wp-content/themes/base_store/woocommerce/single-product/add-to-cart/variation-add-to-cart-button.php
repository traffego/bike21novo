<?php
/**
 * Botões de quantidade e compra para variação selecionada.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.2
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button ds-pdp-qty-actions">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php if ( ! $product->is_sold_individually() ) : ?>
		<div class="ds-pdp-qty-row">
			<span class="ds-pdp-qty-label">Quantidade:</span>
			<div class="ds-qty-stepper">
				<button type="button" class="ds-qty-stepper__btn" data-qty-step="down" aria-label="Diminuir quantidade">-</button>
				<?php
				do_action( 'woocommerce_before_add_to_cart_quantity' );

				woocommerce_quantity_input(
					array(
						'min_value'   => $product->get_min_purchase_quantity(),
						'max_value'   => $product->get_max_purchase_quantity(),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					)
				);

				do_action( 'woocommerce_after_add_to_cart_quantity' );
				?>
				<button type="button" class="ds-qty-stepper__btn" data-qty-step="up" aria-label="Aumentar quantidade">+</button>
			</div>
		</div>
	<?php endif; ?>

	<button type="button" class="ds-btn ds-btn-amber ds-btn-hero ds-btn-block ds-pdp-buy-now-btn" data-buy-now>
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
		Comprar Agora
	</button>

	<button type="submit" class="single_add_to_cart_button ds-btn ds-btn-dark ds-btn-lg ds-btn-block">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
		Adicionar ao Carrinho
	</button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
