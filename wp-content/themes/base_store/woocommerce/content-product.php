<?php
/**
 * Card de produto no loop da loja e categorias usando o componente canônico do Design System.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

store_render_product_card(
	$product,
	array(
		'cta_mode' => 'primary',
	)
);
