<?php
/**
 * Sistema agnóstico de Lista de Desejos / Favoritos persistido em user_meta + cookie.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_get_wishlist_ids' ) ) {
	/**
	 * Retorna os IDs de produtos salvos nos favoritos do usuário atual (logado ou visitante).
	 *
	 * @return int[]
	 */
	function store_get_wishlist_ids(): array {
		static $runtime_ids = null;
		if ( is_array( $runtime_ids ) ) {
			return $runtime_ids;
		}

		$cookie_ids = array();
		if ( ! empty( $_COOKIE['store_wishlist'] ) ) {
			$raw        = sanitize_text_field( wp_unslash( $_COOKIE['store_wishlist'] ) );
			$cookie_ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );
		}

		if ( is_user_logged_in() ) {
			$user_id  = get_current_user_id();
			$user_ids = get_user_meta( $user_id, '_store_wishlist', true );
			$user_ids = is_array( $user_ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) ) : array();
			$ids      = array_values( array_unique( array_merge( $user_ids, $cookie_ids ) ) );
		} else {
			$ids = $cookie_ids;
		}

		// Filtra apenas produtos publicados válidos.
		$valid = array();
		foreach ( $ids as $pid ) {
			if ( 'product' === get_post_type( $pid ) && 'publish' === get_post_status( $pid ) ) {
				$valid[] = $pid;
			}
		}

		$runtime_ids = $valid;
		return $runtime_ids;
	}
}

if ( ! function_exists( 'store_save_wishlist_ids' ) ) {
	/**
	 * Persiste a lista de IDs favoritos em user_meta e cookie HTTP.
	 *
	 * @param int[] $ids IDs de produtos.
	 * @return void
	 */
	function store_save_wishlist_ids( array $ids ): void {
		$clean = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), '_store_wishlist', $clean );
		}

		$val = implode( ',', $clean );
		$_COOKIE['store_wishlist'] = $val;
		if ( ! headers_sent() ) {
			setcookie(
				'store_wishlist',
				$val,
				array(
					'expires'  => time() + ( 30 * DAY_IN_SECONDS ),
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => false,
					'samesite' => 'Lax',
				)
			);
		}
	}
}

if ( ! function_exists( 'store_is_in_wishlist' ) ) {
	/**
	 * Verifica se um produto está nos favoritos.
	 *
	 * @param int $product_id ID do produto.
	 * @return bool
	 */
	function store_is_in_wishlist( int $product_id ): bool {
		return in_array( $product_id, store_get_wishlist_ids(), true );
	}
}

if ( ! function_exists( 'store_get_wishlist_count' ) ) {
	/**
	 * Retorna a quantidade de itens salvos nos favoritos.
	 *
	 * @return int
	 */
	function store_get_wishlist_count(): int {
		return count( store_get_wishlist_ids() );
	}
}

add_action(
	'wp_login',
	function ( $user_login, $user ) {
		if ( ! $user instanceof WP_User || empty( $_COOKIE['store_wishlist'] ) ) {
			return;
		}
		$raw        = sanitize_text_field( wp_unslash( $_COOKIE['store_wishlist'] ) );
		$cookie_ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );
		$existing   = get_user_meta( $user->ID, '_store_wishlist', true );
		$existing   = is_array( $existing ) ? array_values( array_unique( array_filter( array_map( 'absint', $existing ) ) ) ) : array();
		$merged     = array_values( array_unique( array_merge( $existing, $cookie_ids ) ) );
		update_user_meta( $user->ID, '_store_wishlist', $merged );
	},
	10,
	2
);

add_action( 'wp_ajax_store_toggle_wishlist', 'store_ajax_toggle_wishlist' );
add_action( 'wp_ajax_nopriv_store_toggle_wishlist', 'store_ajax_toggle_wishlist' );

if ( ! function_exists( 'store_ajax_toggle_wishlist' ) ) {
	/**
	 * Endpoint AJAX para adicionar ou remover um produto dos favoritos.
	 *
	 * @return void
	 */
	function store_ajax_toggle_wishlist() {
		check_ajax_referer( 'store_frontend_nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$product    = $product_id > 0 ? wc_get_product( $product_id ) : null;
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'Produto inválido.' ), 400 );
		}

		// Recarrega IDs diretamente fora do static cache se necessário
		$raw_cookie = ! empty( $_COOKIE['store_wishlist'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['store_wishlist'] ) ) : '';
		$ids        = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw_cookie ) ) ) ) );
		if ( is_user_logged_in() ) {
			$u_ids = get_user_meta( get_current_user_id(), '_store_wishlist', true );
			$u_ids = is_array( $u_ids ) ? array_map( 'absint', $u_ids ) : array();
			$ids   = array_values( array_unique( array_merge( $u_ids, $ids ) ) );
		}

		$pos = array_search( $product_id, $ids, true );
		if ( false !== $pos ) {
			unset( $ids[ $pos ] );
			$active = false;
		} else {
			$ids[]  = $product_id;
			$active = true;
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );
		store_save_wishlist_ids( $ids );

		wp_send_json_success(
			array(
				'active'     => $active,
				'product_id' => $product_id,
				'count'      => count( $ids ),
				'ids'        => $ids,
			)
		);
	}
}

add_shortcode(
	'store_wishlist',
	function (): string {
		$ids      = store_get_wishlist_ids();
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' );

		ob_start();
		?>
		<div class="ds-wishlist-wrap" data-empty-redirect="<?php echo esc_url( $shop_url ); ?>">
			<div class="ds-wishlist-bar">
				<span class="ds-wishlist-bar__count">
					<strong data-wishlist-total><?php echo esc_html( count( $ids ) ); ?></strong> item(ns) salvo(s)
				</span>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-btn ds-btn-soft">Continuar Explorando</a>
			</div>

			<?php if ( ! empty( $ids ) ) : ?>
				<div class="ds-product-grid ds-cols-3 ds-wishlist-grid">
					<?php
					foreach ( $ids as $pid ) {
						store_render_product_card(
							$pid,
							array(
								'cta_mode'      => 'dual',
								'wishlist_mode' => true,
							)
						);
					}
					?>
				</div>
			<?php endif; ?>

			<div class="ds-empty-box ds-wishlist-empty" <?php echo ! empty( $ids ) ? 'hidden' : ''; ?>>
				<div class="ds-empty-box__icon" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
				</div>
				<h3>Sua lista de desejos está vazia</h3>
				<p>Salve produtos do catálogo clicando no ícone de coração para comparar valores e finalizar sua compra quando quiser.</p>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-btn ds-btn-dark">Explorar Catálogo</a>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
);
