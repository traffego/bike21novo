<?php
/**
 * Paginação numérica agnóstica do catálogo.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
$format  = isset( $format ) ? $format : '';

if ( $total <= 1 ) {
	return;
}
?>
<nav class="woocommerce-pagination ds-pagination" aria-label="Paginação de produtos">
	<?php
	echo wp_kses_post(
		paginate_links(
			apply_filters(
				'woocommerce_pagination_args',
				array(
					'base'      => $base,
					'format'    => $format,
					'add_args'  => false,
					'current'   => max( 1, (int) $current ),
					'total'     => (int) $total,
					'prev_text' => '&lsaquo;',
					'next_text' => '&rsaquo;',
					'type'      => 'list',
					'end_size'  => 2,
					'mid_size'  => 2,
				)
			)
		)
	);
	?>
</nav>
