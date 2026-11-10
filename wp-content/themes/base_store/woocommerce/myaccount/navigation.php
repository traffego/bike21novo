<?php
/**
 * Navegação lateral de Minha Conta com atalho para a Lista de Desejos (`/favoritos/`).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );

$menu_items     = wc_get_account_menu_items();
$wishlist_page  = get_page_by_path( 'favoritos' );
$wishlist_url   = $wishlist_page ? get_permalink( $wishlist_page->ID ) : '';
$wishlist_count = function_exists( 'store_get_wishlist_count' ) ? store_get_wishlist_count() : 0;
?>

<nav class="woocommerce-MyAccount-navigation" aria-label="Menu da Conta">
	<ul>
		<?php foreach ( $menu_items as $endpoint => $label ) : ?>
			<?php if ( 'customer-logout' === $endpoint && '' !== $wishlist_url ) : ?>
				<li class="woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--wishlist<?php echo is_page( 'favoritos' ) ? ' is-active' : ''; ?>">
					<a href="<?php echo esc_url( $wishlist_url ); ?>">
						<span>Lista de Desejos</span>
						<?php if ( $wishlist_count > 0 ) : ?>
							<span class="ds-pill-badge ds-pill-badge--amber"><?php echo esc_html( $wishlist_count ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endif; ?>

			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $endpoint ) ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<span><?php echo esc_html( $label ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
