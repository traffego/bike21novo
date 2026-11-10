<?php
/**
 * Carrinho de Compras (Layout 2 colunas, barra de progresso, cupom, CEP e cross-sells).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

$cart_count   = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$free_thresh  = Store_Glossary::get_float( 'free_shipping_threshold' );
$cart_sub_val = WC()->cart ? (float) WC()->cart->get_displayed_subtotal() : 0.0;
$remaining    = $free_thresh > 0 ? max( 0.0, $free_thresh - $cart_sub_val ) : 0.0;
$progress_pct = $free_thresh > 0 ? min( 100, (int) round( ( $cart_sub_val / $free_thresh ) * 100 ) ) : 0;
$free_ok      = $free_thresh > 0 && $remaining <= 0.0;
$coupon_ph    = store_term( 'coupon_input_placeholder' ) ?: 'Código do cupom';
$shop_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' );
$saved_cep    = WC()->customer ? (string) WC()->customer->get_shipping_postcode() : '';
?>

<div class="ds-funnel-wrap">
	<?php echo store_render_funnel_steps( 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<div class="ds-cart-layout">
	<div class="ds-cart-main">
		<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
			<?php do_action( 'woocommerce_before_cart_table' ); ?>

			<div class="ds-panel-card">
				<div class="ds-card-head">
					<h2 class="ds-card-head__title">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
						<?php
						echo esc_html(
							sprintf(
								'Seu Carrinho (%d %s)',
								$cart_count,
								1 === $cart_count ? 'item' : 'itens'
							)
						);
						?>
					</h2>
					<span class="ds-pill-badge ds-pill-badge--emerald">Estoque Reservado</span>
				</div>

				<?php if ( $free_thresh > 0 ) : ?>
					<div class="ds-free-shipping-banner <?php echo $free_ok ? 'is-unlocked' : ''; ?>" style="margin-bottom:16px;">
						<div class="ds-free-shipping-banner__top">
							<span>
								<?php if ( $free_ok ) : ?>
									Parabéns! Seu pedido atingiu o valor para <strong>Frete Grátis</strong> nas regiões elegíveis.
								<?php else : ?>
									Faltam <strong><?php echo esc_html( store_format_brl( $remaining ) ); ?></strong> para liberar <strong>Frete Grátis</strong> nas regiões elegíveis.
								<?php endif; ?>
							</span>
							<strong><?php echo esc_html( $progress_pct ); ?>%</strong>
						</div>
						<div class="ds-free-shipping-bar" aria-hidden="true">
							<div class="ds-free-shipping-bar__fill" style="width: <?php echo esc_attr( $progress_pct ); ?>%;"></div>
						</div>
					</div>
				<?php endif; ?>

				<div class="shop_table shop_table_responsive cart woocommerce-cart-form__contents ds-cart-items-list">
					<?php do_action( 'woocommerce_before_cart_contents' ); ?>

					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						$visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

						if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
							$cart_item_name    = WC()->cart->get_item_product_name( $cart_item, $_product );
							$product_name      = apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key );
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							$parent_product    = $_product->get_parent_id() ? wc_get_product( $_product->get_parent_id() ) : $_product;
							$brand             = $parent_product instanceof WC_Product ? store_get_product_brand( $parent_product ) : '';
							$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
							$line_total_raw    = (float) WC()->cart->get_product_price( $_product );
							$line_val_num      = (float) wc_get_price_to_display( $_product ) * (int) $cart_item['quantity'];
							$line_pricing      = store_calculate_pricing_summary( $line_val_num );
							?>
							<div class="woocommerce-cart-form__cart-item ds-cart-item-card <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
								<div class="ds-cart-item-card__left">
									<div class="product-thumbnail ds-cart-item-card__thumb">
										<?php
										if ( ! $product_permalink ) {
											echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										} else {
											printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}
										?>
									</div>

									<div class="product-name ds-cart-item-card__info" data-title="Produto">
										<?php if ( '' !== $brand ) : ?>
											<span class="ds-cart-item-card__brand"><?php echo esc_html( $brand ); ?></span>
										<?php endif; ?>

										<h3 class="ds-cart-item-card__title">
											<?php
											if ( ! $product_permalink ) {
												echo wp_kses_post( $product_name );
											} else {
												echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $cart_item_name ), $cart_item, $cart_item_key ) );
											}
											?>
										</h3>

										<?php
										do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

										$item_meta_html = wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name );
										if ( '' !== trim( (string) $item_meta_html ) ) {
											echo '<div class="ds-cart-item-card__meta">' . $item_meta_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										}

										if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
											echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">Disponível sob encomenda</p>', $product_id ) );
										}
										?>

										<div class="product-price ds-cart-item-card__meta" data-title="Preço">
											Unitário: <?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</div>
									</div>
								</div>

								<div class="ds-cart-item-card__right">
									<div class="product-quantity ds-qty-stepper" data-title="Quantidade">
										<?php if ( ! $_product->is_sold_individually() ) : ?>
											<button type="button" class="ds-qty-stepper__btn" data-qty-step="down" aria-label="Diminuir quantidade">&minus;</button>
										<?php endif; ?>

										<?php
										if ( $_product->is_sold_individually() ) {
											$min_quantity = 1;
											$max_quantity = 1;
										} else {
											$min_quantity = 1;
											$max_quantity = $_product->get_max_purchase_quantity();
										}

										$product_quantity = woocommerce_quantity_input(
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max_quantity,
												'min_value'    => $min_quantity,
												'product_name' => $product_name,
											),
											$_product,
											false
										);

										echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>

										<?php if ( ! $_product->is_sold_individually() ) : ?>
											<button type="button" class="ds-qty-stepper__btn" data-qty-step="up" aria-label="Aumentar quantidade">+</button>
										<?php endif; ?>
									</div>

									<div class="product-subtotal ds-cart-item-card__subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
										<strong><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
										<?php if ( $line_pricing['cash_percent'] > 0 && $line_pricing['cash_price'] > 0 ) : ?>
											<small><?php echo esc_html( store_format_brl( $line_pricing['cash_price'] ) . ' no PIX' ); ?></small>
										<?php endif; ?>
									</div>

									<span class="product-remove">
										<?php
										$trash_icon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>';
										echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											'woocommerce_cart_item_remove_link',
											sprintf(
												'<a role="button" href="%s" class="remove ds-cart-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
												esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
												esc_attr( sprintf( 'Remover %s do carrinho', wp_strip_all_tags( $product_name ) ) ),
												esc_attr( $product_id ),
												esc_attr( $_product->get_sku() ),
												$trash_icon
											),
											$cart_item_key
										);
										?>
									</span>
								</div>
							</div>
							<?php
						}
					}
					?>

					<?php do_action( 'woocommerce_cart_contents' ); ?>
				</div>

				<div class="ds-cart-actions-bar actions">
					<?php if ( wc_coupons_enabled() ) : ?>
						<div class="coupon ds-cart-coupon-box">
							<label for="coupon_code" class="screen-reader-text">Cupom de desconto</label>
							<input
								type="text"
								name="coupon_code"
								class="input-text"
								id="coupon_code"
								value=""
								placeholder="<?php echo esc_attr( $coupon_ph ); ?>"
							/>
							<button
								type="submit"
								class="button ds-btn ds-btn-dark<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
								name="apply_coupon"
								value="Aplicar"
							>
								Aplicar
							</button>
							<?php do_action( 'woocommerce_cart_coupon' ); ?>
						</div>
					<?php endif; ?>

					<div class="ds-cart-actions-right">
						<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-btn ds-btn-soft">Continuar Comprando</a>
						<button
							type="submit"
							class="button ds-cart-update-btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
							name="update_cart"
							value="Atualizar carrinho"
						>
							Atualizar carrinho
						</button>
						<?php do_action( 'woocommerce_cart_actions' ); ?>
						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</div>
				</div>

				<?php
				$applied_coupons = WC()->cart ? WC()->cart->get_coupons() : array();
				if ( ! empty( $applied_coupons ) ) :
					?>
					<div class="ds-coupon-list">
						<?php foreach ( $applied_coupons as $code => $coupon ) : ?>
							<span class="ds-coupon-chip">
								Cupom <strong><?php echo esc_html( strtoupper( $code ) ); ?></strong>
								<a
									role="button"
									href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_cart_url() ) ); ?>"
									class="woocommerce-remove-coupon ds-coupon-remove"
									data-coupon="<?php echo esc_attr( $code ); ?>"
									aria-label="<?php echo esc_attr( sprintf( 'Remover cupom %s', $code ) ); ?>"
								>&times;</a>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php do_action( 'woocommerce_after_cart_contents' ); ?>
			</div>

			<?php do_action( 'woocommerce_after_cart_table' ); ?>
		</form>

		<?php if ( WC()->cart && WC()->cart->needs_shipping() ) : ?>
			<div class="ds-panel-card ds-shipping-section-card" data-store-shipping-calc data-product-id="0">
				<div class="ds-card-head">
					<h3 class="ds-card-head__title">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><circle cx="7" cy="18" r="2"/><path d="M15 18H9"/><circle cx="17" cy="18" r="2"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-1"/></svg>
						Calcular Frete &amp; Prazo de Entrega
					</h3>
					<?php if ( '' !== store_term( 'shipping_badge_label' ) ) : ?>
						<span class="ds-shipping-header-note"><?php echo esc_html( store_term( 'shipping_badge_label' ) ); ?></span>
					<?php endif; ?>
				</div>

				<div class="ds-cep-inline-wrap ds-field">
					<label for="storeCartCepInput" class="screen-reader-text">CEP de Entrega</label>
					<input
						id="storeCartCepInput"
						type="text"
						class="ds-cep-input"
						data-mask="cep"
						data-shipping-cep
						maxlength="9"
						placeholder="00000-000"
						value="<?php echo esc_attr( $saved_cep ); ?>"
					/>
					<button type="button" class="ds-btn ds-btn-dark ds-viacep-btn" data-shipping-submit>Calcular Frete</button>
				</div>
				<div class="ds-shipping-results" data-shipping-results hidden aria-live="polite"></div>
			</div>
		<?php endif; ?>

		<?php woocommerce_cross_sell_display( 3, 3 ); ?>
	</div>

	<aside class="ds-cart-sidebar">
		<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

		<div class="cart-collaterals">
			<?php
			if ( has_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' ) ) {
				remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 );
			}
			do_action( 'woocommerce_cart_collaterals' );
			?>
		</div>
	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
