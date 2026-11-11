<?php
/**
 * Galeria principal da Página de Produto (Imagem em destaque, miniaturas e selos de confiança).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_id  = $product->get_id();
$title       = $product->get_name();
$main_img_id = (int) $product->get_image_id();
$gallery_ids = array_values( array_unique( array_filter( array_map( 'absint', $product->get_gallery_image_ids() ) ) ) );

$all_image_ids = array();
if ( $main_img_id > 0 ) {
	$all_image_ids[] = $main_img_id;
}
foreach ( $gallery_ids as $gid ) {
	if ( ! in_array( $gid, $all_image_ids, true ) ) {
		$all_image_ids[] = $gid;
	}
}

$primary_id     = ! empty( $all_image_ids[0] ) ? (int) $all_image_ids[0] : 0;
$primary_src    = $primary_id ? (string) wp_get_attachment_image_url( $primary_id, 'woocommerce_single' ) : wc_placeholder_img_src( 'woocommerce_single' );
$primary_srcset = $primary_id ? (string) wp_get_attachment_image_srcset( $primary_id, 'woocommerce_single' ) : '';
$primary_sizes  = $primary_id ? (string) wp_get_attachment_image_sizes( $primary_id, 'woocommerce_single' ) : '';

$top_badge  = store_get_product_badge( $product );
$pricing    = store_get_product_pricing_summary( $product );
$ship_badge = store_term( 'shipping_badge_label' ) ?: 'Frete Grátis';
$is_in_wl   = function_exists( 'store_is_in_wishlist' ) ? store_is_in_wishlist( $product_id ) : false;

$prep_title = store_term( 'prep_quality_title' ) ?: 'Conferência Técnica';
$prep_desc  = store_term( 'prep_badge_label' );
$ship_title = store_term( 'shipping_modal_name' ) ?: 'Entrega Segura';
$ship_desc  = store_term( 'shipping_badge_label' );
$war_title  = store_term( 'warranty_title' ) ?: 'Garantia Oficial';
$war_desc   = store_term( 'guarantee_seal' );
?>
<div class="ds-pdp-gallery-card">
	<div class="ds-pdp-main-media" data-pdp-media-zoom>
		<?php if ( $top_badge || $pricing['qualifies_free_shipping'] ) : ?>
			<div class="ds-pdp-floating-badges">
				<?php if ( $top_badge ) : ?>
					<span class="ds-product-badge ds-product-badge--<?php echo esc_attr( $top_badge['variant'] ); ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
						<?php echo esc_html( $top_badge['text'] ); ?>
					</span>
				<?php endif; ?>
				<?php if ( $pricing['qualifies_free_shipping'] && ( ! $top_badge || 'emerald' !== $top_badge['variant'] ) ) : ?>
					<span class="ds-product-badge ds-product-badge--dark">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><circle cx="7" cy="18" r="2"/><path d="M15 18H9"/><circle cx="17" cy="18" r="2"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-1"/></svg>
						<?php echo esc_html( $ship_badge ); ?>
					</span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<button
			type="button"
			class="ds-wishlist-btn<?php echo $is_in_wl ? ' is-active' : ''; ?>"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-remove-card="0"
			aria-pressed="<?php echo $is_in_wl ? 'true' : 'false'; ?>"
			aria-label="<?php echo esc_attr( $is_in_wl ? 'Remover dos favoritos' : 'Salvar nos favoritos' ); ?>"
			title="<?php echo esc_attr( $is_in_wl ? 'Remover dos favoritos' : 'Salvar nos favoritos' ); ?>"
		>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
		</button>

		<img
			id="storePdpMainImg"
			src="<?php echo esc_url( $primary_src ); ?>"
			<?php if ( '' !== $primary_srcset ) : ?>
				srcset="<?php echo esc_attr( $primary_srcset ); ?>"
			<?php endif; ?>
			<?php if ( '' !== $primary_sizes ) : ?>
				sizes="<?php echo esc_attr( $primary_sizes ); ?>"
			<?php endif; ?>
			alt="<?php echo esc_attr( $title ); ?>"
			class="wp-post-image"
		/>

		<span class="ds-pdp-zoom-hint" aria-hidden="true">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
			Passe o mouse para ampliar
		</span>
	</div>

	<?php if ( count( $all_image_ids ) > 1 ) : ?>
		<div class="ds-pdp-thumbs" role="list" aria-label="Miniaturas do produto">
			<?php foreach ( $all_image_ids as $idx => $img_id ) : ?>
				<?php
				$full_src    = (string) wp_get_attachment_image_url( $img_id, 'woocommerce_single' );
				$full_srcset = (string) wp_get_attachment_image_srcset( $img_id, 'woocommerce_single' );
				$thumb_src   = (string) wp_get_attachment_image_url( $img_id, 'woocommerce_gallery_thumbnail' );
				if ( '' === $thumb_src ) {
					$thumb_src = $full_src;
				}
				?>
				<button
					type="button"
					class="ds-pdp-thumb<?php echo 0 === $idx ? ' is-active' : ''; ?>"
					data-full-src="<?php echo esc_url( $full_src ); ?>"
					data-full-srcset="<?php echo esc_attr( $full_srcset ); ?>"
					aria-label="<?php echo esc_attr( sprintf( 'Visualizar imagem %d de %s', $idx + 1, $title ) ); ?>"
				>
					<img src="<?php echo esc_url( $thumb_src ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="ds-pdp-trust-grid">
		<div class="ds-pdp-trust-item">
			<span class="ds-pdp-trust-item__icon ds-pdp-trust-item__icon--amber" aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
			</span>
			<div>
				<strong class="ds-pdp-trust-item__title"><?php echo esc_html( $prep_title ); ?></strong>
				<?php if ( '' !== $prep_desc ) : ?>
					<span class="ds-pdp-trust-item__desc"><?php echo esc_html( $prep_desc ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="ds-pdp-trust-item">
			<span class="ds-pdp-trust-item__icon ds-pdp-trust-item__icon--blue" aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><circle cx="7" cy="18" r="2"/><path d="M15 18H9"/><circle cx="17" cy="18" r="2"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-1"/></svg>
			</span>
			<div>
				<strong class="ds-pdp-trust-item__title"><?php echo esc_html( $ship_title ); ?></strong>
				<?php if ( '' !== $ship_desc ) : ?>
					<span class="ds-pdp-trust-item__desc"><?php echo esc_html( $ship_desc ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="ds-pdp-trust-item">
			<span class="ds-pdp-trust-item__icon ds-pdp-trust-item__icon--emerald" aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
			</span>
			<div>
				<strong class="ds-pdp-trust-item__title"><?php echo esc_html( $war_title ); ?></strong>
				<?php if ( '' !== $war_desc ) : ?>
					<span class="ds-pdp-trust-item__desc"><?php echo esc_html( $war_desc ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
