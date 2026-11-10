<?php
/**
 * Card canônico de produto do Design System (utilizado em vitrines, catálogo, busca e favoritos).
 *
 * Variáveis esperadas no escopo:
 * @var WC_Product $store_card_product
 * @var array      $store_card_args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $store_card_product ) || ! $store_card_product instanceof WC_Product ) {
	return;
}

$args         = isset( $store_card_args ) && is_array( $store_card_args ) ? $store_card_args : array();
$cta_mode     = isset( $args['cta_mode'] ) ? (string) $args['cta_mode'] : 'primary';
$is_wl_mode   = ! empty( $args['wishlist_mode'] );
$featured_cls = ! empty( $args['featured_ring'] ) ? ' is-highlighted' : '';

$product_id  = $store_card_product->get_id();
$permalink   = $store_card_product->get_permalink();
$title       = $store_card_product->get_name();
$short_desc  = $store_card_product->get_short_description();
$excerpt     = '' !== trim( wp_strip_all_tags( $short_desc ) )
	? wp_trim_words( wp_strip_all_tags( $short_desc ), 14 )
	: wp_trim_words( wp_strip_all_tags( $store_card_product->get_description() ), 14 );
$brand       = store_get_product_brand( $store_card_product );
$badges      = store_get_product_spec_badges( $store_card_product, 3 );
$top_badge   = store_get_product_badge( $store_card_product );
$pricing     = store_get_product_pricing_summary( $store_card_product );
$is_in_wl    = function_exists( 'store_is_in_wishlist' ) ? store_is_in_wishlist( $product_id ) : false;
$image_id    = $store_card_product->get_image_id();
$image_html  = $image_id
	? wp_get_attachment_image(
		$image_id,
		'woocommerce_thumbnail',
		false,
		array(
			'class'   => 'ds-product-card__img',
			'loading' => 'lazy',
			'alt'     => esc_attr( $title ),
		)
	)
	: '<img src="' . esc_url( wc_placeholder_img_src( 'woocommerce_thumbnail' ) ) . '" alt="' . esc_attr( $title ) . '" class="ds-product-card__img" loading="lazy" />';
?>
<article class="ds-product-card<?php echo esc_attr( $featured_cls ); ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>">
	<div class="ds-product-card__top">
		<div class="ds-product-card__media">
			<?php if ( $top_badge ) : ?>
				<span class="ds-product-badge ds-product-badge--<?php echo esc_attr( $top_badge['variant'] ); ?>">
					<?php echo esc_html( $top_badge['text'] ); ?>
				</span>
			<?php endif; ?>

			<button
				type="button"
				class="ds-wishlist-btn<?php echo $is_in_wl ? ' is-active' : ''; ?>"
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
				data-remove-card="<?php echo $is_wl_mode ? '1' : '0'; ?>"
				aria-pressed="<?php echo $is_in_wl ? 'true' : 'false'; ?>"
				aria-label="<?php echo esc_attr( $is_in_wl ? 'Remover dos favoritos' : 'Salvar nos favoritos' ); ?>"
				title="<?php echo esc_attr( $is_in_wl ? 'Remover dos favoritos' : 'Salvar nos favoritos' ); ?>"
			>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
			</button>

			<a href="<?php echo esc_url( $permalink ); ?>" class="ds-product-card__media-link" aria-label="<?php echo esc_attr( $title ); ?>">
				<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>

		<?php if ( ! empty( $badges ) ) : ?>
			<div class="ds-product-card__specs">
				<?php foreach ( $badges as $spec ) : ?>
					<span class="ds-spec-pill<?php echo ! empty( $spec['accent'] ) ? ' ds-spec-pill--accent' : ''; ?>">
						<?php echo esc_html( $spec['value'] ); ?>
					</span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $brand ) : ?>
			<span class="ds-product-card__brand"><?php echo esc_html( $brand ); ?></span>
		<?php endif; ?>

		<h3 class="ds-product-card__title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
		</h3>

		<?php if ( '' !== $excerpt ) : ?>
			<p class="ds-product-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
		<?php endif; ?>
	</div>

	<div class="ds-product-card__footer">
		<div class="ds-product-card__pricing">
			<?php if ( $pricing['discount_pct'] > 0 ) : ?>
				<span class="ds-price-old"><?php echo esc_html( store_format_brl( $pricing['regular_price'] ) ); ?></span>
			<?php else : ?>
				<span class="ds-price-old ds-price-old--spacer" aria-hidden="true">&nbsp;</span>
			<?php endif; ?>

			<div class="ds-price-row">
				<strong class="ds-price-current"><?php echo esc_html( store_format_brl( $pricing['base_price'] ) ); ?></strong>
				<?php if ( $pricing['discount_pct'] > 0 ) : ?>
					<span class="ds-price-off">-<?php echo esc_html( $pricing['discount_pct'] ); ?>%</span>
				<?php endif; ?>
			</div>

			<?php if ( $pricing['installments_count'] > 1 ) : ?>
				<div class="ds-price-installments">
					ou <strong><?php echo esc_html( $pricing['installments_count'] ); ?>x de <?php echo esc_html( store_format_brl( $pricing['installment_value'] ) ); ?></strong>
					<?php echo '' !== $pricing['installments_label'] ? ' ' . esc_html( $pricing['installments_label'] ) : ''; ?>
				</div>
			<?php endif; ?>

			<?php if ( $pricing['cash_percent'] > 0 ) : ?>
				<div class="ds-price-cash">
					<strong><?php echo esc_html( store_format_brl( $pricing['cash_price'] ) ); ?></strong>
					<?php echo '' !== $pricing['cash_label'] ? ' ' . esc_html( $pricing['cash_label'] ) : ''; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( 'dual' === $cta_mode ) : ?>
			<div class="ds-product-card__actions ds-product-card__actions--dual">
				<a href="<?php echo esc_url( $permalink ); ?>" class="ds-btn ds-btn-soft">Ver Detalhes</a>
				<?php if ( $store_card_product->is_type( 'simple' ) && $store_card_product->is_purchasable() && $store_card_product->is_in_stock() ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => $product_id, 'store_buy_now' => '1' ), wc_get_checkout_url() ) ); ?>" class="ds-btn ds-btn-amber">Comprar</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="ds-btn ds-btn-amber">Comprar</a>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="ds-product-card__actions">
				<a href="<?php echo esc_url( $permalink ); ?>" class="ds-btn ds-btn-dark ds-btn-block">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
					Comprar Agora
				</a>
			</div>
		<?php endif; ?>
	</div>
</article>
