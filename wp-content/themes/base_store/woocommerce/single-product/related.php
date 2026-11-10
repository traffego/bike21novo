<?php
/**
 * Produtos complementares (Cross-sells reais + relacionados do WooCommerce) na Página de Produto.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     10.3.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$current_id = $product->get_id();
$pool_ids   = array();

foreach ( (array) $product->get_cross_sell_ids() as $cid ) {
	$cid = absint( $cid );
	if ( $cid > 0 && $cid !== $current_id && ! in_array( $cid, $pool_ids, true ) ) {
		$pool_ids[] = $cid;
	}
}

if ( isset( $related_products ) && is_array( $related_products ) ) {
	foreach ( $related_products as $rel ) {
		$rid = $rel instanceof WC_Product ? $rel->get_id() : absint( $rel );
		if ( $rid > 0 && $rid !== $current_id && ! in_array( $rid, $pool_ids, true ) ) {
			$pool_ids[] = $rid;
		}
	}
}

if ( count( $pool_ids ) < 3 ) {
	foreach ( (array) wc_get_related_products( $current_id, 6 ) as $rid ) {
		$rid = absint( $rid );
		if ( $rid > 0 && $rid !== $current_id && ! in_array( $rid, $pool_ids, true ) ) {
			$pool_ids[] = $rid;
		}
	}
}

$display_products = array();
foreach ( $pool_ids as $pid ) {
	$candidate = wc_get_product( $pid );
	if ( $candidate instanceof WC_Product && $candidate->is_visible() ) {
		$display_products[] = $candidate;
	}
	if ( count( $display_products ) >= 3 ) {
		break;
	}
}

if ( empty( $display_products ) ) {
	return;
}

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' );
?>
<section class="related products ds-panel-card" aria-label="Produtos relacionados e complementares">
	<div class="ds-showcase-header">
		<div>
			<h2 class="ds-section-title">Combine e Aproveite Junto</h2>
			<p class="ds-section-subtitle">Itens selecionados e compatíveis para completar seu pedido.</p>
		</div>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-link-accent">Ver catálogo completo &rarr;</a>
	</div>

	<div class="ds-product-grid ds-cols-3">
		<?php
		foreach ( $display_products as $rel_product ) {
			store_render_product_card(
				$rel_product,
				array(
					'cta_mode' => 'primary',
				)
			);
		}
		?>
	</div>
</section>
<?php
wp_reset_postdata();
