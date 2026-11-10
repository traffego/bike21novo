<?php
/**
 * Calculadora e tabela de medidas configurável via banco de dados (`size_guide_config`).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_render_size_guide_inner' ) ) {
	/**
	 * Monta o conteúdo interno compartilhado do Guia de Medidas a partir do JSON no banco.
	 *
	 * @param array  $cfg  Configuração JSON decodificada.
	 * @param string $mode Modo de exibição ('compact', 'full' ou 'modal').
	 * @return string
	 */
	function store_render_size_guide_inner( array $cfg, string $mode = 'compact' ): string {
		$min     = isset( $cfg['min'] ) ? (float) $cfg['min'] : 0.0;
		$max     = isset( $cfg['max'] ) ? (float) $cfg['max'] : 0.0;
		$step    = isset( $cfg['step'] ) && (float) $cfg['step'] > 0 ? (float) $cfg['step'] : 1.0;
		$def     = isset( $cfg['default_value'] ) ? (float) $cfg['default_value'] : $min;
		$divider = isset( $cfg['scale_divider'] ) && (float) $cfg['scale_divider'] > 0 ? (float) $cfg['scale_divider'] : 1.0;
		$unit    = isset( $cfg['unit'] ) ? (string) $cfg['unit'] : '';
		$rows    = ! empty( $cfg['rows'] ) && is_array( $cfg['rows'] ) ? $cfg['rows'] : array();
		$cols    = ! empty( $cfg['columns'] ) && is_array( $cfg['columns'] ) ? $cfg['columns'] : array();

		$matched_row = null;
		foreach ( $rows as $row ) {
			if ( isset( $row['min'], $row['max'] ) && $def >= (float) $row['min'] && $def <= (float) $row['max'] ) {
				$matched_row = $row;
				break;
			}
		}
		if ( ! $matched_row && ! empty( $rows[0] ) ) {
			$matched_row = $rows[0];
		}

		$format_measure = static function ( float $raw ) use ( $divider, $unit ): string {
			$scaled = $divider > 1 ? number_format( $raw / $divider, 2, ',', '' ) : (string) $raw;
			return trim( $scaled . ( '' !== $unit ? ' ' . $unit : '' ) );
		};

		$wa_url     = Store_Glossary::whatsapp_link();
		$guide_page = get_page_by_path( 'guia-de-tamanhos' );
		$guide_url  = $guide_page ? get_permalink( $guide_page->ID ) : '';
		$uid        = wp_unique_id( 'ds-size-guide-' );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $uid ); ?>"
			class="ds-size-guide ds-size-guide--<?php echo esc_attr( $mode ); ?>"
			data-size-config="<?php echo esc_attr( wp_json_encode( $cfg, JSON_UNESCAPED_UNICODE ) ); ?>"
		>
			<div class="ds-size-guide__grid">
				<div class="ds-size-guide__info">
					<?php if ( ! empty( $cfg['badge'] ) ) : ?>
						<span class="ds-pill-badge ds-pill-badge--amber"><?php echo esc_html( (string) $cfg['badge'] ); ?></span>
					<?php endif; ?>

					<?php if ( ! empty( $cfg['title'] ) ) : ?>
						<h3 class="ds-size-guide__title"><?php echo esc_html( (string) $cfg['title'] ); ?></h3>
					<?php endif; ?>

					<?php if ( ! empty( $cfg['subtitle'] ) ) : ?>
						<p class="ds-size-guide__subtitle"><?php echo esc_html( (string) $cfg['subtitle'] ); ?></p>
					<?php endif; ?>

					<div class="ds-size-guide__actions">
						<?php if ( '' !== $wa_url ) : ?>
							<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-dark">
								Falar com Especialista no WhatsApp
							</a>
						<?php endif; ?>
						<?php if ( 'compact' === $mode && '' !== $guide_url ) : ?>
							<a href="<?php echo esc_url( $guide_url ); ?>" class="ds-btn ds-btn-soft">
								Ver Guia Completo
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="ds-size-guide__panel">
					<div class="ds-size-guide__slider-row">
						<label for="<?php echo esc_attr( $uid ); ?>-range" class="ds-size-guide__label">
							<?php echo esc_html( ! empty( $cfg['slider_label'] ) ? (string) $cfg['slider_label'] : (string) ( $cfg['input_label'] ?? '' ) ); ?>
						</label>
						<strong class="ds-size-guide__value" data-sg-display><?php echo esc_html( $format_measure( $def ) ); ?></strong>
					</div>

					<?php if ( $max > $min ) : ?>
						<input
							id="<?php echo esc_attr( $uid ); ?>-range"
							type="range"
							class="ds-size-guide__range"
							min="<?php echo esc_attr( $min ); ?>"
							max="<?php echo esc_attr( $max ); ?>"
							step="<?php echo esc_attr( $step ); ?>"
							value="<?php echo esc_attr( $def ); ?>"
							data-sg-input
						/>
					<?php endif; ?>

					<?php if ( $matched_row ) : ?>
						<div class="ds-size-guide__result" data-sg-result>
							<span class="ds-size-guide__rec-prefix" data-sg-rec-title>
								<?php
								$prefix = ! empty( $cfg['recommendation_prefix'] ) ? (string) $cfg['recommendation_prefix'] : 'Recomendação:';
								echo esc_html( trim( $prefix . ' ' . (string) ( $matched_row['label'] ?? '' ) ) );
								?>
							</span>
							<?php if ( ! empty( $matched_row['note'] ) ) : ?>
								<p class="ds-size-guide__rec-note" data-sg-rec-note><?php echo esc_html( (string) $matched_row['note'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'compact' !== $mode && ! empty( $rows ) ) : ?>
				<div class="ds-size-guide__table-wrap">
					<table class="ds-table ds-size-guide__table">
						<?php if ( ! empty( $cols ) ) : ?>
							<thead>
								<tr>
									<?php foreach ( $cols as $col_title ) : ?>
										<th><?php echo esc_html( (string) $col_title ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
						<?php endif; ?>
						<tbody>
							<?php foreach ( $rows as $idx => $row ) : ?>
								<?php
								$is_match = ( $matched_row && ( $row['label'] ?? '' ) === ( $matched_row['label'] ?? '' ) );
								?>
								<tr data-sg-row-idx="<?php echo esc_attr( $idx ); ?>" class="<?php echo $is_match ? 'is-matched' : ''; ?>">
									<td class="ds-table__strong"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></td>
									<td><?php echo esc_html( (string) ( $row['range_text'] ?? '' ) ); ?></td>
									<?php if ( isset( $row['secondary'] ) && '' !== (string) $row['secondary'] ) : ?>
										<td><?php echo esc_html( (string) $row['secondary'] ); ?></td>
									<?php endif; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'store_is_size_guide_attribute' ) ) {
	/**
	 * Verifica se um atributo de produto ou variação está vinculado ao Guia de Medidas nas opções da loja.
	 *
	 * @param string $attribute_slug Taxonomia ou slug do atributo.
	 * @return bool
	 */
	function store_is_size_guide_attribute( string $attribute_slug ): bool {
		$configured = Store_Glossary::get_list( 'size_guide_attributes' );
		if ( empty( $configured ) ) {
			return false;
		}

		$normalize = static function ( string $slug ): string {
			$slug = strtolower( trim( $slug ) );
			$slug = preg_replace( '/^attribute_/', '', $slug );
			return (string) preg_replace( '/^pa_/', '', $slug );
		};

		$target = $normalize( $attribute_slug );
		if ( '' === $target ) {
			return false;
		}

		foreach ( $configured as $item ) {
			if ( $normalize( (string) $item ) === $target ) {
				return true;
			}
		}

		return false;
	}
}

add_shortcode(
	'store_size_guide',
	function ( $atts ): string {
		$args = shortcode_atts(
			array(
				'mode' => 'compact',
			),
			$atts,
			'store_size_guide'
		);

		$cfg = Store_Glossary::get_json( 'size_guide_config' );
		if ( empty( $cfg ) || empty( $cfg['rows'] ) ) {
			return '';
		}

		$mode  = in_array( $args['mode'], array( 'compact', 'full', 'modal' ), true ) ? $args['mode'] : 'compact';
		$inner = store_render_size_guide_inner( $cfg, $mode );

		if ( 'modal' === $mode ) {
			ob_start();
			?>
			<div id="storeSizeGuideModal" class="ds-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="Guia de Medidas">
				<div class="ds-modal__backdrop" data-store-close-modal></div>
				<div class="ds-modal__dialog">
					<button type="button" class="ds-modal__close" data-store-close-modal aria-label="Fechar janela">&times;</button>
					<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="ds-modal__footer">
						<button type="button" class="ds-btn ds-btn-dark ds-btn-block" data-store-close-modal>Entendi, voltar para a compra</button>
					</div>
				</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		return $inner;
	}
);
