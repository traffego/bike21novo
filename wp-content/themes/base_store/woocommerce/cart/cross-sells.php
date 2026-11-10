<?php
/**
 * Produtos Relacionados / Cross-sells no Carrinho usando o Card Canônico.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( $cross_sells ) :
	$heading = apply_filters( 'woocommerce_product_cross_sells_products_heading', 'Aproveite para completar seu pedido' );
	?>
	<div class="cross-sells ds-cart-cross-sells">
		<?php if ( $heading ) : ?>
			<div class="ds-cart-cross-sells__head">
				<h3><?php echo esc_html( $heading ); ?></h3>
			</div>
		<?php endif; ?>

		<div class="ds-product-grid ds-cols-3">
			<?php
			foreach ( $cross_sells as $cross_sell ) :
				$post_object = get_post( $cross_sell->get_id() );
				setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
				store_render_product_card( $cross_sell, array( 'cta_mode' => 'dual' ) );
			endforeach;
			?>
		</div>
	</div>
	<?php
endif;

wp_reset_postdata();
