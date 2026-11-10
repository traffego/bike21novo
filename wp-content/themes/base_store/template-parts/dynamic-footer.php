<?php
/**
 * Exibe o rodapé ativo do Elementor com fallback para o rodapé dinâmico do tema pai.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'store_render_active_template' ) && store_render_active_template( 'footer' ) ) {
	return;
}

require get_template_directory() . '/template-parts/dynamic-footer.php';
