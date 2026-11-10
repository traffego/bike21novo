<?php
/**
 * Carrinho Vazio no padrão visual do Design System.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( has_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message' ) ) {
	remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
}

do_action( 'woocommerce_cart_is_empty' );

$shop_url = function_exists( 'wc_get_page_permalink' )
	? apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) )
	: home_url( '/loja/' );
?>
<div class="ds-empty-box wc-empty-cart-message">
	<div class="ds-empty-box__icon" aria-hidden="true">
		<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
	</div>
	<h3>Seu carrinho está vazio</h3>
	<p>Você ainda não adicionou nenhum item ao carrinho. Explore nosso catálogo completo para conferir as ofertas e opções com desconto à vista.</p>
	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<a class="ds-btn ds-btn-dark wc-backward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" href="<?php echo esc_url( $shop_url ); ?>">
			Voltar para a Loja
		</a>
	<?php endif; ?>
</div>
