<?php
/**
 * Conteúdo principal da Página de Produto (Galeria 7 col + Buybox 5 col + Abas + Cross-sells).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$brand        = store_get_product_brand( $product );
$pricing      = store_get_product_pricing_summary( $product );
$quick_badges = store_get_product_spec_badges( $product, 4 );
$sku          = $product->get_sku();
$short_desc   = trim( wp_strip_all_tags( $product->get_short_description() ) );
$wa_prefix    = store_term( 'whatsapp_pdp_prefix' );
$wa_link      = Store_Glossary::whatsapp_link( trim( $wa_prefix . ' ' . $product->get_name() ) );
$store_loc    = store_term( 'store_location' );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'ds-pdp-wrap', $product ); ?>>
	<div class="ds-pdp-top-grid">
		<div class="ds-pdp-left-column">
			<?php wc_get_template( 'single-product/product-image.php' ); ?>
		</div>

		<div class="ds-pdp-buybox">
			<div class="ds-pdp-meta-row">
				<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
					<?php if ( '' !== $brand ) : ?>
						<span class="ds-spec-pill"><?php echo esc_html( 'Marca: ' . $brand ); ?></span>
					<?php endif; ?>
					<?php if ( $product->is_in_stock() ) : ?>
						<span class="ds-pill-badge ds-pill-badge--emerald">
							<?php echo esc_html( '' !== $store_loc ? 'Pronta Entrega (' . $store_loc . ')' : 'Pronta Entrega' ); ?>
						</span>
					<?php else : ?>
						<span class="ds-pill-badge ds-pill-badge--rose">Indisponível</span>
					<?php endif; ?>
				</div>
				<?php if ( '' !== $sku ) : ?>
					<span class="ds-pdp-sku">SKU: <strong data-sku-value><?php echo esc_html( $sku ); ?></strong></span>
				<?php endif; ?>
			</div>

			<h1 class="ds-pdp-title"><?php the_title(); ?></h1>

			<?php if ( ! empty( $quick_badges ) ) : ?>
				<div class="ds-pdp-quick-specs">
					<?php foreach ( $quick_badges as $spec ) : ?>
						<span class="ds-spec-pill<?php echo ! empty( $spec['accent'] ) ? ' ds-spec-pill--accent' : ''; ?>">
							<?php echo esc_html( $spec['label'] . ': ' . $spec['value'] ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $short_desc ) : ?>
				<p class="ds-section-subtitle" style="margin-top:0;"><?php echo esc_html( $short_desc ); ?></p>
			<?php endif; ?>

			<div class="ds-pdp-price-card" aria-live="polite">
				<?php if ( $pricing['discount_pct'] > 0 ) : ?>
					<div class="ds-pdp-price-old-row">
						<span class="ds-price-old"><?php echo esc_html( store_format_brl( $pricing['regular_price'] ) ); ?></span>
						<span class="ds-price-off">-<?php echo esc_html( $pricing['discount_pct'] ); ?>% OFF</span>
					</div>
				<?php endif; ?>

				<div class="ds-pdp-price-main-row">
					<strong class="ds-pdp-price-big">
						<?php echo esc_html( store_format_brl( $pricing['cash_percent'] > 0 ? $pricing['cash_price'] : $pricing['base_price'] ) ); ?>
					</strong>
					<?php if ( $pricing['cash_percent'] > 0 && '' !== $pricing['cash_label'] ) : ?>
						<span class="ds-pdp-price-cash-tag"><?php echo esc_html( $pricing['cash_label'] ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( $pricing['cash_percent'] > 0 ) : ?>
					<div class="ds-pdp-price-installment">
						<span>
							ou <?php echo esc_html( store_format_brl( $pricing['base_price'] ) ); ?>
							<?php if ( $pricing['installments_count'] > 1 ) : ?>
								em <strong><?php echo esc_html( $pricing['installments_count'] ); ?>x de <?php echo esc_html( store_format_brl( $pricing['installment_value'] ) ); ?></strong>
								<?php echo esc_html( $pricing['installments_label'] ); ?>
							<?php endif; ?>
						</span>
					</div>
				<?php elseif ( $pricing['installments_count'] > 1 ) : ?>
					<div class="ds-pdp-price-installment">
						<span>
							em até <strong><?php echo esc_html( $pricing['installments_count'] ); ?>x de <?php echo esc_html( store_format_brl( $pricing['installment_value'] ) ); ?></strong>
							<?php echo esc_html( $pricing['installments_label'] ); ?>
						</span>
					</div>
				<?php endif; ?>
			</div>

			<?php woocommerce_template_single_add_to_cart(); ?>

			<?php if ( '' !== $wa_link ) : ?>
				<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-whatsapp ds-btn-block">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
					Tirar Dúvidas com Especialista via WhatsApp
				</a>
			<?php endif; ?>

			<div class="ds-pdp-cep-box" data-store-shipping-calc data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
				<label for="storePdpCepInput" class="ds-pdp-cep-label">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><circle cx="7" cy="18" r="2"/><path d="M15 18H9"/><circle cx="17" cy="18" r="2"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-1"/></svg>
					Simule o prazo e frete para seu CEP
				</label>
				<div class="ds-cep-inline-wrap ds-field">
					<input
						id="storePdpCepInput"
						type="text"
						class="ds-cep-input"
						data-mask="cep"
						data-shipping-cep
						maxlength="9"
						placeholder="00000-000"
					/>
					<button type="button" class="ds-btn ds-btn-dark ds-viacep-btn" data-shipping-submit>Calcular</button>
				</div>
				<div class="ds-shipping-results" data-shipping-results hidden aria-live="polite"></div>
			</div>
		</div>
	</div>

	<?php
	echo do_shortcode( '[store_size_guide mode="modal"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wc_get_template( 'single-product/tabs/tabs.php' );
	wc_get_template( 'single-product/related.php' );

	if ( class_exists( 'WC_Structured_Data' ) && function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
		WC()->structured_data->generate_product_data( $product );
	}
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
