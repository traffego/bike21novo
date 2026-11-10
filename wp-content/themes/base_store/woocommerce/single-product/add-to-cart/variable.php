<?php
/**
 * Compra de produto variável com seleção dinâmica em pílulas sincronizada com o select nativo WooCommerce.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
$size_cfg        = Store_Glossary::get_json( 'size_guide_config' );
$has_size_guide  = ! empty( $size_cfg['rows'] ) && is_array( $size_cfg['rows'] );

do_action( 'woocommerce_before_add_to_cart_form' );
?>
<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', 'Este produto está temporariamente indisponível.' ) ); ?></p>
	<?php else : ?>
		<div class="ds-pdp-variations-wrap">
			<?php
			$size_guide_attrs        = Store_Glossary::get_list( 'size_guide_attributes' );
			$has_configured_sg_attrs = ! empty( $size_guide_attrs );
			$attr_idx                = 0;
			foreach ( $attributes as $attribute_name => $options ) :
				$field_name = 'attribute_' . sanitize_title( $attribute_name );
				$pill_items = array();

				if ( taxonomy_exists( $attribute_name ) ) {
					$terms = wc_get_product_terms(
						$product->get_id(),
						$attribute_name,
						array(
							'fields' => 'all',
						)
					);
					$by_slug = array();
					foreach ( $terms as $term ) {
						if ( $term instanceof WP_Term ) {
							$by_slug[ $term->slug ] = $term->name;
						}
					}
					foreach ( $options as $opt ) {
						if ( isset( $by_slug[ $opt ] ) ) {
							$pill_items[] = array(
								'value' => $opt,
								'label' => $by_slug[ $opt ],
							);
						} else {
							$pill_items[] = array(
								'value' => $opt,
								'label' => $opt,
							);
						}
					}
				} else {
					foreach ( $options as $opt ) {
						$pill_items[] = array(
							'value' => $opt,
							'label' => $opt,
						);
					}
				}
					$show_size_guide_btn = $has_size_guide && (
						$has_configured_sg_attrs
							? ( function_exists( 'store_is_size_guide_attribute' ) && store_is_size_guide_attribute( (string) $attribute_name ) )
							: 0 === $attr_idx
					);
				?>
				<div class="ds-variation-attr-group" data-attr-name="<?php echo esc_attr( $field_name ); ?>">
					<div class="ds-variation-attr-head">
						<span class="ds-variation-attr-label">
							<?php echo esc_html( wc_attribute_label( $attribute_name, $product ) ); ?><span class="ds-variation-attr-selected" data-attr-selected></span>
						</span>
						<?php if ( $show_size_guide_btn ) : ?>
							<button type="button" class="ds-size-guide-trigger" data-open-size-guide>
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>
								Guia de Medidas
							</button>
						<?php endif; ?>
					</div>

					<div class="ds-variation-pills" role="group" aria-label="<?php echo esc_attr( wc_attribute_label( $attribute_name, $product ) ); ?>">
						<?php foreach ( $pill_items as $pill ) : ?>
							<button
								type="button"
								class="ds-var-pill"
								data-attr="<?php echo esc_attr( $field_name ); ?>"
								data-value="<?php echo esc_attr( $pill['value'] ); ?>"
							>
								<?php echo esc_html( $pill['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
				$attr_idx++;
			endforeach;
			?>

			<div class="ds-var-reset-row">
				<button type="button" class="ds-var-reset">Limpar opções selecionadas</button>
			</div>
		</div>

		<table class="variations ds-sr-selects" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<tr>
						<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></th>
						<td class="value">
							<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $options,
									'attribute' => $attribute_name,
									'product'   => $product,
								)
							);
							echo end( $attribute_keys ) === $attribute_name
								? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="Limpar opções">Limpar</a>' ) )
								: '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<template class="wc-product-gallery-default-template"><?php echo wc_get_product_gallery_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>
<?php
do_action( 'woocommerce_after_add_to_cart_form' );
