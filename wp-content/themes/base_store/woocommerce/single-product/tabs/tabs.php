<?php
/**
 * Abas técnicas agnósticas da Página de Produto (Descrição, Ficha Técnica, Envio & Preparação, Garantia).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$description_raw = trim( (string) $product->get_description() );
if ( '' === $description_raw ) {
	$description_raw = trim( (string) $product->get_short_description() );
}
$description_html = '' !== $description_raw
	? apply_filters( 'the_content', $description_raw )
	: '<p>Consulte a aba de especificações técnicas ou fale com nossa equipe para orientações detalhadas sobre este item.</p>';

$tech_rows      = store_get_product_technical_rows( $product );
$prep_title     = store_term( 'prep_quality_title' ) ?: 'Preparação & Conferência de Envio';
$prep_desc      = store_term( 'prep_quality_desc' );
$prep_bullets   = Store_Glossary::get_list( 'prep_quality_bullets', "\n" );
$ship_modal     = store_term( 'shipping_modal_name' );
$ship_footer    = store_term( 'shipping_footer_desc' );
$free_thresh    = Store_Glossary::get_float( 'free_shipping_threshold' );
$war_title      = store_term( 'warranty_title' ) ?: 'Garantia Oficial & Nota Fiscal';
$war_desc       = store_term( 'warranty_desc' );
$war_seal       = store_term( 'guarantee_seal' );
$reverse_term   = store_term( 'reverse_logistics_term' );
$war_page       = get_page_by_path( 'garantia-e-trocas' );
$war_url        = $war_page ? get_permalink( $war_page->ID ) : '';
$delivery_page  = get_page_by_path( 'entrega-e-montagem' );
$delivery_url   = $delivery_page ? get_permalink( $delivery_page->ID ) : '';
?>
<section class="ds-pdp-tabs-card" aria-label="Informações detalhadas do produto">
	<div class="ds-tabs-nav" role="tablist">
		<button type="button" class="ds-tab-btn is-active" data-tab-target="dsTabDesc" role="tab" aria-selected="true" aria-controls="dsTabDesc">
			Descrição Completa
		</button>
		<button type="button" class="ds-tab-btn" data-tab-target="dsTabSpecs" role="tab" aria-selected="false" aria-controls="dsTabSpecs">
			Ficha Técnica
		</button>
		<button type="button" class="ds-tab-btn" data-tab-target="dsTabPrep" role="tab" aria-selected="false" aria-controls="dsTabPrep">
			Envio &amp; Preparação
		</button>
		<button type="button" class="ds-tab-btn" data-tab-target="dsTabWarranty" role="tab" aria-selected="false" aria-controls="dsTabWarranty">
			Garantia Oficial
		</button>
	</div>

	<div id="dsTabDesc" class="ds-tab-panel" role="tabpanel">
		<div style="color:var(--ds-slate-700);font-size:var(--ds-text-sm);line-height:1.75;">
			<?php echo wp_kses_post( $description_html ); ?>
		</div>
	</div>

	<div id="dsTabSpecs" class="ds-tab-panel" role="tabpanel" hidden>
		<?php if ( ! empty( $tech_rows ) ) : ?>
			<div class="ds-specs-grid">
				<?php foreach ( $tech_rows as $row ) : ?>
					<div class="ds-spec-row">
						<span class="ds-spec-row__label"><?php echo esc_html( $row['label'] ); ?></span>
						<span class="ds-spec-row__value"><?php echo esc_html( $row['value'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="ds-section-subtitle">Especificações técnicas adicionais disponíveis via atendimento consultivo.</p>
		<?php endif; ?>
	</div>

	<div id="dsTabPrep" class="ds-tab-panel" role="tabpanel" hidden>
		<div style="display:flex;flex-direction:column;gap:14px;">
			<h3 class="ds-section-title" style="font-size:var(--ds-text-lg);"><?php echo esc_html( $prep_title ); ?></h3>
			<?php if ( '' !== $prep_desc ) : ?>
				<p class="ds-section-subtitle" style="margin-top:0;"><?php echo esc_html( $prep_desc ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $prep_bullets ) ) : ?>
				<ul style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:8px;color:var(--ds-slate-700);font-size:var(--ds-text-xs);line-height:1.6;">
					<?php foreach ( $prep_bullets as $bullet ) : ?>
						<li><?php echo esc_html( $bullet ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( '' !== $ship_modal || $free_thresh > 0 ) : ?>
				<div class="ds-spec-row" style="margin-top:4px;">
					<span class="ds-spec-row__label">
						<?php echo esc_html( '' !== $ship_modal ? $ship_modal : 'Frete segurado' ); ?>
						<?php echo '' !== $ship_footer ? ' — ' . esc_html( $ship_footer ) : ''; ?>
					</span>
					<?php if ( $free_thresh > 0 ) : ?>
						<span class="ds-spec-row__value" style="color:var(--ds-emerald-700);">
							Frete grátis acima de <?php echo esc_html( store_format_brl( $free_thresh ) ); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $delivery_url ) : ?>
				<div>
					<a href="<?php echo esc_url( $delivery_url ); ?>" class="ds-link-accent">Ver detalhes de entrega e preparação &rarr;</a>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div id="dsTabWarranty" class="ds-tab-panel" role="tabpanel" hidden>
		<div style="display:flex;flex-direction:column;gap:14px;">
			<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
				<h3 class="ds-section-title" style="font-size:var(--ds-text-lg);"><?php echo esc_html( $war_title ); ?></h3>
				<?php if ( '' !== $war_seal ) : ?>
					<span class="ds-pill-badge ds-pill-badge--emerald"><?php echo esc_html( $war_seal ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $war_desc ) : ?>
				<div style="color:var(--ds-slate-700);font-size:var(--ds-text-xs);line-height:1.7;">
					<?php echo wp_kses_post( nl2br( esc_html( $war_desc ) ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $reverse_term ) : ?>
				<div class="ds-spec-row">
					<span class="ds-spec-row__label">Trocas &amp; Devoluções (7 dias)</span>
					<span class="ds-spec-row__value"><?php echo esc_html( $reverse_term ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $war_url ) : ?>
				<div>
					<a href="<?php echo esc_url( $war_url ); ?>" class="ds-link-accent">Consultar política completa de garantia e trocas &rarr;</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
