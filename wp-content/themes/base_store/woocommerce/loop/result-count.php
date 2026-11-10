<?php
/**
 * Contador de resultados exibidos no catálogo.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 10.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="woocommerce-result-count" role="status" aria-relevant="all" <?php echo ( empty( $orderedby ) || 1 === intval( $total ) ) ? '' : 'data-is-sorted-by="true"'; ?>>
	<?php
	if ( 1 === intval( $total ) ) {
		echo 'Exibindo <strong>1</strong> resultado encontrado';
	} elseif ( $total <= $per_page || -1 === $per_page ) {
		/* translators: %d: total de produtos */
		echo wp_kses_post( sprintf( 'Exibindo todos os <strong>%d</strong> resultados encontrados', (int) $total ) );
	} else {
		$first = ( $per_page * $current ) - $per_page + 1;
		$last  = min( $total, $per_page * $current );
		echo wp_kses_post(
			sprintf(
				'Exibindo <strong>%1$d&ndash;%2$d</strong> de <strong>%3$d</strong> resultados encontrados',
				(int) $first,
				(int) $last,
				(int) $total
			)
		);
	}
	?>
</p>
