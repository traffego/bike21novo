<?php
/**
 * Seletor de ordenação da vitrine do catálogo.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 9.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id_suffix = wp_unique_id();
?>
<form class="woocommerce-ordering" method="get">
	<label for="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>" class="ds-orderby-label">Ordenar por:</label>
	<select
		name="orderby"
		class="orderby"
		id="woocommerce-orderby-<?php echo esc_attr( $id_suffix ); ?>"
		aria-label="Ordenar produtos"
	>
		<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
			<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="paged" value="1" />
	<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
</form>
