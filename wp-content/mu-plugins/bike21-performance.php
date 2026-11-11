<?php
/**
 * Plugin Name: Bike21 Performance & Runtime Tuning
 * Description: Otimizações de memória, remoção de bloqueios AJAX desnecessários do WooCommerce e limpeza de assets redundantes no front-end.
 * Version: 1.0.0
 * Author: Bike21
 *
 * @package Bike21_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converte string de limite de memória PHP (ex: '256M', '1G') para bytes.
 *
 * @param string $limit Valor configurado no PHP.
 * @return int Bytes equivalentes, ou -1 para ilimitado.
 */
function bike21_perf_memory_to_bytes( string $limit ): int {
	$limit = trim( $limit );
	if ( '-1' === $limit || '' === $limit ) {
		return -1;
	}

	$unit  = strtolower( substr( $limit, -1 ) );
	$value = (int) $limit;

	switch ( $unit ) {
		case 'g':
			return $value * 1024 * 1024 * 1024;
		case 'm':
			return $value * 1024 * 1024;
		case 'k':
			return $value * 1024;
		default:
			return $value;
	}
}

/**
 * Eleva o limite de memória do PHP em tempo de execução para suportar Elementor + WooCommerce
 * sem ciclos excessivos de garbage collection no servidor remoto.
 */
$bike21_current_mem = (string) ini_get( 'memory_limit' );
$bike21_current_b   = bike21_perf_memory_to_bytes( $bike21_current_mem );
$bike21_target_b    = 512 * 1024 * 1024;

if ( -1 !== $bike21_current_b && $bike21_current_b < $bike21_target_b ) {
	// phpcs:ignore WordPress.PHP.IniSet.memory_limit_Disallowed
	@ini_set( 'memory_limit', '512M' );
}

add_filter(
	'admin_memory_limit',
	static function (): string {
		return '512M';
	},
	99
);

/**
 * Desativa a chamada AJAX bloqueante `wc-cart-fragments` quando o carrinho está vazio
 * e o visitante não está nas páginas de Carrinho ou Checkout.
 * A atualização do contador após cliques em "Adicionar ao carrinho" continua funcionando
 * via evento nativo `added_to_cart` e `StoreConfig` em `store-frontend.js`.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return;
		}

		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$has_cart_cookie = ! empty( $_COOKIE['woocommerce_items_in_cart'] );

		if ( ! $has_cart_cookie ) {
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	},
	100
);

/**
 * Remove scripts e metatags redundantes do núcleo do WordPress no front-end
 * (emojis JS/CSS, RSD, WLW Manifest, shortlink e gerador), reduzindo o tamanho do HTML e requisições.
 */
add_action(
	'init',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
);
