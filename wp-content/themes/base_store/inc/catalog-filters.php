<?php
/**
 * Motor agnóstico de filtros facetados, ordenação e busca de produtos do catálogo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_get_catalog_filter_attributes' ) ) {
	/**
	 * Retorna as taxonomias de atributos configuradas para filtragem na loja.
	 * Lê da opção configurada no banco e realiza fallback para atributos globais do WooCommerce.
	 *
	 * @return string[]
	 */
	function store_get_catalog_filter_attributes(): array {
		$configured = Store_Glossary::get_list( 'catalog_filter_attributes' );
		$valid      = array();

		if ( ! empty( $configured ) ) {
			foreach ( $configured as $slug ) {
				$tax = 0 === strpos( $slug, 'pa_' ) ? $slug : wc_attribute_taxonomy_name( $slug );
				if ( taxonomy_exists( $tax ) && ! in_array( $tax, $valid, true ) ) {
					$valid[] = $tax;
				}
			}
		}

		if ( empty( $valid ) && function_exists( 'wc_get_attribute_taxonomies' ) ) {
			foreach ( wc_get_attribute_taxonomies() as $attr ) {
				$tax = wc_attribute_taxonomy_name( $attr->attribute_name );
				if ( taxonomy_exists( $tax ) && ! in_array( $tax, $valid, true ) ) {
					$valid[] = $tax;
				}
			}
		}

		return $valid;
	}
}

if ( ! function_exists( 'store_parse_active_filter_params' ) ) {
	/**
	 * Interpreta parâmetros ativos de filtragem da URL atual.
	 *
	 * @return array{
	 *   attributes: array<string, string[]>,
	 *   min_price: float|null,
	 *   max_price: float|null,
	 *   on_sale: bool,
	 *   instock: bool,
	 *   orderby: string,
	 *   search: string
	 * }
	 */
	function store_parse_active_filter_params(): array {
		$attrs = array();
		foreach ( store_get_catalog_filter_attributes() as $tax ) {
			$short = preg_replace( '/^pa_/', '', $tax );
			$key   = 'filter_' . $short;
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$raw = wc_clean( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( is_array( $raw ) ) {
					$slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', $raw ) ) ) );
				} else {
					$slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', explode( ',', (string) $raw ) ) ) ) );
				}
				if ( ! empty( $slugs ) ) {
					$attrs[ $tax ] = $slugs;
				}
			}
		}

		$min_price = null;
		if ( isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw_min = str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['min_price'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_numeric( $raw_min ) ) {
				$min_price = max( 0.0, (float) $raw_min );
			}
		}

		$max_price = null;
		if ( isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw_max = str_replace( ',', '.', sanitize_text_field( wp_unslash( $_GET['max_price'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( is_numeric( $raw_max ) ) {
				$max_price = max( 0.0, (float) $raw_max );
			}
		}

		$search = '';
		if ( isset( $_GET['s'] ) && '' !== trim( (string) $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['q'] ) && '' !== trim( (string) $_GET['q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field( wp_unslash( $_GET['q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return array(
			'attributes' => $attrs,
			'min_price'  => $min_price,
			'max_price'  => $max_price,
			'on_sale'    => ! empty( $_GET['on_sale'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'instock'    => ! empty( $_GET['instock'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'orderby'    => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search'     => $search,
		);
	}
}

add_action(
	'woocommerce_product_query',
	function ( WP_Query $q ) {
		$parsed = store_parse_active_filter_params();

		if ( ! empty( $parsed['attributes'] ) ) {
			$tax_query = (array) $q->get( 'tax_query' );
			if ( empty( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			foreach ( $parsed['attributes'] as $taxonomy => $slugs ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $slugs,
					'operator' => 'IN',
				);
			}
			$q->set( 'tax_query', $tax_query );
		}

		$meta_query = (array) $q->get( 'meta_query' );
		if ( null !== $parsed['min_price'] || null !== $parsed['max_price'] ) {
			if ( null !== $parsed['min_price'] && null !== $parsed['max_price'] ) {
				$meta_query[] = array(
					'key'     => '_price',
					'value'   => array( $parsed['min_price'], $parsed['max_price'] ),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL(12,2)',
				);
			} elseif ( null !== $parsed['min_price'] ) {
				$meta_query[] = array(
					'key'     => '_price',
					'value'   => $parsed['min_price'],
					'compare' => '>=',
					'type'    => 'DECIMAL(12,2)',
				);
			} elseif ( null !== $parsed['max_price'] ) {
				$meta_query[] = array(
					'key'     => '_price',
					'value'   => $parsed['max_price'],
					'compare' => '<=',
					'type'    => 'DECIMAL(12,2)',
				);
			}
		}

		if ( $parsed['instock'] ) {
			$meta_query[] = array(
				'key'     => '_stock_status',
				'value'   => 'instock',
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$q->set( 'meta_query', $meta_query );
		}

		if ( $parsed['on_sale'] && function_exists( 'wc_get_product_ids_on_sale' ) ) {
			$sale_ids = array_values( array_filter( array_map( 'absint', wc_get_product_ids_on_sale() ) ) );
			$existing = (array) $q->get( 'post__in' );
			if ( ! empty( $existing ) ) {
				$intersect = array_values( array_intersect( $existing, $sale_ids ) );
				$q->set( 'post__in', ! empty( $intersect ) ? $intersect : array( 0 ) );
			} else {
				$q->set( 'post__in', ! empty( $sale_ids ) ? $sale_ids : array( 0 ) );
			}
		}

		if ( 'discount' === $parsed['orderby'] ) {
			$q->set( 'store_sort_discount', true );
		}
	},
	20,
	1
);

add_filter(
	'woocommerce_catalog_orderby',
	function ( array $options ): array {
		return array(
			'menu_order' => 'Relevância',
			'popularity' => 'Mais Vendidos',
			'price'      => 'Menor Preço',
			'price-desc' => 'Maior Preço',
			'date'       => 'Lançamentos',
			'discount'   => 'Maiores Descontos',
		);
	}
);

add_filter(
	'woocommerce_get_catalog_ordering_args',
	function ( array $args, string $orderby = '', string $order = '' ): array {
		if ( 'discount' === $orderby ) {
			$args['orderby']  = 'date';
			$args['order']    = 'DESC';
			$args['meta_key'] = '';
		}
		return $args;
	},
	20,
	3
);

add_filter(
	'posts_clauses',
	function ( array $clauses, WP_Query $query ): array {
		global $wpdb;

		if ( ! $query->get( 'store_sort_discount' ) ) {
			return $clauses;
		}

		$clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS ds_reg ON ({$wpdb->posts}.ID = ds_reg.post_id AND ds_reg.meta_key = '_regular_price')";
		$clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS ds_act ON ({$wpdb->posts}.ID = ds_act.post_id AND ds_act.meta_key = '_price')";
		$clauses['orderby'] = " (CASE WHEN CAST(ds_reg.meta_value AS DECIMAL(12,2)) > CAST(ds_act.meta_value AS DECIMAL(12,2)) AND CAST(ds_reg.meta_value AS DECIMAL(12,2)) > 0 THEN ((CAST(ds_reg.meta_value AS DECIMAL(12,2)) - CAST(ds_act.meta_value AS DECIMAL(12,2))) / CAST(ds_reg.meta_value AS DECIMAL(12,2))) ELSE 0 END) DESC, {$wpdb->posts}.post_date DESC ";

		return $clauses;
	},
	20,
	2
);

if ( ! function_exists( 'store_get_catalog_price_bounds' ) ) {
	/**
	 * Consulta menor e maior preço reais do catálogo publicado.
	 *
	 * @return array{min:int,max:int}
	 */
	function store_get_catalog_price_bounds(): array {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT MIN(CAST(pm.meta_value AS DECIMAL(12,2))) AS min_p, MAX(CAST(pm.meta_value AS DECIMAL(12,2))) AS max_p
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_price'
			   AND pm.meta_value != ''
			   AND CAST(pm.meta_value AS DECIMAL(12,2)) > 0
			   AND p.post_type = 'product'
			   AND p.post_status = 'publish'"
		);

		$min = $row && null !== $row->min_p ? (int) floor( (float) $row->min_p ) : 0;
		$max = $row && null !== $row->max_p ? (int) ceil( (float) $row->max_p ) : 10000;
		if ( $max <= $min ) {
			$max = $min + 1000;
		}

		return array(
			'min' => $min,
			'max' => $max,
		);
	}
}

if ( ! function_exists( 'store_get_catalog_base_url' ) ) {
	/**
	 * Retorna a URL base limpa da página atual do catálogo (preservando rota e termo de busca, removendo filtros).
	 *
	 * @return string
	 */
	function store_get_catalog_base_url(): string {
		if ( is_product_taxonomy() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					return $link;
				}
			}
		}

		if ( is_search() ) {
			return add_query_arg(
				array(
					's'         => get_search_query(),
					'post_type' => 'product',
				),
				home_url( '/' )
			);
		}

		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' );
		return $shop_url ?: home_url( '/' );
	}
}

if ( ! function_exists( 'store_get_catalog_subcategories' ) ) {
	/**
	 * Retorna subcategorias reais vinculadas ao termo atual ou categorias raiz ativas.
	 *
	 * @return array<int,array{id:int,name:string,slug:string,count:int,url:string,active:bool}>
	 */
	function store_get_catalog_subcategories(): array {
		$current_term = is_product_category() ? get_queried_object() : null;
		$parent_id    = $current_term instanceof WP_Term ? (int) $current_term->term_id : 0;

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => false,
			)
		);

		// Se a categoria folha não tiver filhos, lista irmãs do mesmo nível para navegação fluida.
		if ( ( is_wp_error( $terms ) || empty( $terms ) ) && $current_term instanceof WP_Term && $current_term->parent > 0 ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'parent'     => (int) $current_term->parent,
					'hide_empty' => false,
				)
			);
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$items = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term || 'uncategorized' === $term->slug || 0 === (int) $term->count ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$items[] = array(
				'id'     => (int) $term->term_id,
				'name'   => $term->name,
				'slug'   => $term->slug,
				'count'  => (int) $term->count,
				'url'    => $link,
				'active' => $current_term instanceof WP_Term && (int) $current_term->term_id === (int) $term->term_id,
			);
		}

		return $items;
	}
}

if ( ! function_exists( 'store_get_catalog_filter_groups' ) ) {
	/**
	 * Monta os grupos dinâmicos de filtros de atributos sem fixar taxonomias no código.
	 *
	 * @param array $parsed Parâmetros já interpretados por store_parse_active_filter_params().
	 * @return array
	 */
	function store_get_catalog_filter_groups( array $parsed ): array {
		$taxonomies = store_get_catalog_filter_attributes();
		$groups     = array();

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
				)
			);
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			$short     = preg_replace( '/^pa_/', '', $taxonomy );
			$param_key = 'filter_' . $short;
			$selected  = isset( $parsed['attributes'][ $taxonomy ] ) ? $parsed['attributes'][ $taxonomy ] : array();
			$max_len   = 0;
			$options   = array();

			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term || (int) $term->count <= 0 ) {
					continue;
				}
				$max_len   = max( $max_len, mb_strlen( $term->name ) );
				$options[] = array(
					'slug'    => $term->slug,
					'name'    => $term->name,
					'count'   => (int) $term->count,
					'checked' => in_array( $term->slug, $selected, true ),
				);
			}

			if ( empty( $options ) ) {
				continue;
			}

			$groups[] = array(
				'taxonomy' => $taxonomy,
				'param'    => $param_key,
				'label'    => wc_attribute_label( $taxonomy ),
				'display'  => ( count( $options ) <= 8 && $max_len <= 12 ) ? 'chips' : 'list',
				'terms'    => $options,
			);
		}

		return $groups;
	}
}

if ( ! function_exists( 'store_render_catalog_sidebar' ) ) {
	/**
	 * Renderiza a barra lateral agnóstica de filtros do catálogo.
	 *
	 * @return string
	 */
	function store_render_catalog_sidebar(): string {
		$parsed       = store_parse_active_filter_params();
		$groups       = store_get_catalog_filter_groups( $parsed );
		$price_bounds = store_get_catalog_price_bounds();
		$clear_url    = store_get_catalog_base_url();
		$form_action  = is_search() ? home_url( '/' ) : $clear_url;
		$has_active   = ! empty( $parsed['attributes'] ) || null !== $parsed['min_price'] || null !== $parsed['max_price'] || $parsed['on_sale'] || $parsed['instock'];

		$cur_min = null !== $parsed['min_price'] ? (int) floor( $parsed['min_price'] ) : $price_bounds['min'];
		$cur_max = null !== $parsed['max_price'] ? (int) ceil( $parsed['max_price'] ) : $price_bounds['max'];
		$guide_pg = get_page_by_path( 'guia-de-tamanhos' );
		$guide_url = $guide_pg ? get_permalink( $guide_pg->ID ) : '';

		ob_start();
		?>
		<form class="ds-filter-form" method="get" action="<?php echo esc_url( $form_action ); ?>">
			<?php if ( is_search() ) : ?>
				<input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" />
				<input type="hidden" name="post_type" value="product" />
			<?php endif; ?>
			<?php if ( '' !== $parsed['orderby'] ) : ?>
				<input type="hidden" name="orderby" value="<?php echo esc_attr( $parsed['orderby'] ); ?>" />
			<?php endif; ?>

			<div class="ds-filter-head">
				<h3 class="ds-filter-head__title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
					Filtros do Catálogo
				</h3>
				<?php if ( $has_active ) : ?>
					<a href="<?php echo esc_url( $clear_url ); ?>" class="ds-filter-head__clear">Limpar Tudo</a>
				<?php endif; ?>
			</div>

			<?php foreach ( $groups as $group ) : ?>
				<div class="ds-filter-group">
					<div class="ds-filter-group__head">
						<h4 class="ds-filter-group__title"><?php echo esc_html( $group['label'] ); ?></h4>
						<?php if ( store_is_size_guide_attribute( $group['taxonomy'] ) && '' !== $guide_url ) : ?>
							<a href="<?php echo esc_url( $guide_url ); ?>" class="ds-filter-group__help">Guia de Medidas</a>
						<?php endif; ?>
					</div>

					<?php if ( 'chips' === $group['display'] ) : ?>
						<div class="ds-filter-chips">
							<?php foreach ( $group['terms'] as $term ) : ?>
								<label class="ds-filter-chip<?php echo $term['checked'] ? ' is-checked' : ''; ?>">
									<input
										type="checkbox"
										name="<?php echo esc_attr( $group['param'] ); ?>[]"
										value="<?php echo esc_attr( $term['slug'] ); ?>"
										<?php checked( $term['checked'] ); ?>
									/>
									<span><?php echo esc_html( $term['name'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="ds-filter-list">
							<?php foreach ( $group['terms'] as $term ) : ?>
								<label class="ds-filter-check">
									<span class="ds-filter-check__left">
										<input
											type="checkbox"
											name="<?php echo esc_attr( $group['param'] ); ?>[]"
											value="<?php echo esc_attr( $term['slug'] ); ?>"
											<?php checked( $term['checked'] ); ?>
										/>
										<span><?php echo esc_html( $term['name'] ); ?></span>
									</span>
									<span class="ds-filter-check__count"><?php echo esc_html( $term['count'] ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<div class="ds-filter-group">
				<h4 class="ds-filter-group__title">Faixa de Preço</h4>
				<div class="ds-price-inputs">
					<div class="ds-price-input-box">
						<span>De</span>
						<input
							type="number"
							name="min_price"
							min="0"
							step="10"
							placeholder="<?php echo esc_attr( $price_bounds['min'] ); ?>"
							value="<?php echo null !== $parsed['min_price'] ? esc_attr( $cur_min ) : ''; ?>"
						/>
					</div>
					<span class="ds-price-sep">-</span>
					<div class="ds-price-input-box">
						<span>Até</span>
						<input
							type="number"
							name="max_price"
							min="0"
							step="10"
							placeholder="<?php echo esc_attr( $price_bounds['max'] ); ?>"
							value="<?php echo null !== $parsed['max_price'] ? esc_attr( $cur_max ) : ''; ?>"
						/>
					</div>
				</div>
			</div>

			<div class="ds-filter-group">
				<h4 class="ds-filter-group__title">Disponibilidade &amp; Ofertas</h4>
				<div class="ds-filter-list">
					<label class="ds-filter-check">
						<span class="ds-filter-check__left">
							<input type="checkbox" name="on_sale" value="1" <?php checked( $parsed['on_sale'] ); ?> />
							<span>Em Promoção / Oferta</span>
						</span>
					</label>
					<label class="ds-filter-check">
						<span class="ds-filter-check__left">
							<input type="checkbox" name="instock" value="1" <?php checked( $parsed['instock'] ); ?> />
							<span>Pronta Entrega</span>
						</span>
					</label>
				</div>
			</div>

			<button type="submit" class="ds-btn ds-btn-dark ds-btn-block">Aplicar Filtros</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'store_render_search_results_markup' ) ) {
	/**
	 * Renderiza a view unificada de busca (compatível tanto com `is_search()` nativo quanto com `/busca/`).
	 *
	 * @param string $search_term Termo pesquisado.
	 * @return string
	 */
	function store_render_search_results_markup( string $search_term = '' ): string {
		$search_term = trim( $search_term );
		if ( '' === $search_term ) {
			$parsed      = store_parse_active_filter_params();
			$search_term = $parsed['search'];
		}

		$paged = max( 1, get_query_var( 'paged' ) ? (int) get_query_var( 'paged' ) : ( isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 12;

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			),
		);

		if ( '' !== $search_term ) {
			$args['s'] = $search_term;
		}

		$q            = new WP_Query( $args );
		$total_found  = (int) $q->found_posts;
		$popular      = Store_Glossary::get_list( 'popular_search_terms' );
		$placeholder  = store_term( 'search_placeholder' ) ?: 'Buscar produtos na loja...';
		$shop_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' );

		ob_start();
		?>
		<div class="ds-search-page-wrap">
			<div class="ds-panel-card ds-search-hero-card">
				<div class="ds-search-hero-card__top">
					<div>
						<span class="ds-eyebrow">Busca no Catálogo</span>
						<h1 class="ds-search-hero-card__title">
							<?php if ( '' !== $search_term ) : ?>
								Resultados para: <span class="ds-text-amber">"<?php echo esc_html( $search_term ); ?>"</span>
							<?php else : ?>
								Explore nosso catálogo completo
							<?php endif; ?>
						</h1>
					</div>
					<div class="ds-search-hero-card__count">
						Encontrados <strong><?php echo esc_html( $total_found ); ?> produto(s)</strong> disponível(is)
					</div>
				</div>

				<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="ds-search-inline-form">
					<input type="hidden" name="post_type" value="product" />
					<input
						type="search"
						name="s"
						value="<?php echo esc_attr( $search_term ); ?>"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						class="ds-search-inline-form__input"
					/>
					<button type="submit" class="ds-btn ds-btn-dark">Buscar Produtos</button>
				</form>

				<?php if ( ! empty( $popular ) ) : ?>
					<div class="ds-search-popular">
						<span class="ds-search-popular__label">Mais buscados:</span>
						<?php foreach ( $popular as $term_item ) : ?>
							<a
								href="<?php echo esc_url( add_query_arg( array( 's' => $term_item, 'post_type' => 'product' ), home_url( '/' ) ) ); ?>"
								class="ds-chip-link"
							>
								<?php echo esc_html( $term_item ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $q->have_posts() ) : ?>
				<div class="ds-product-grid ds-cols-4">
					<?php
					while ( $q->have_posts() ) {
						$q->the_post();
						$prod = wc_get_product( get_the_ID() );
						if ( $prod instanceof WC_Product && $prod->is_visible() ) {
							store_render_product_card( $prod, array( 'cta_mode' => 'primary' ) );
						}
					}
					wp_reset_postdata();
					?>
				</div>

				<?php if ( $q->max_num_pages > 1 ) : ?>
					<nav class="woocommerce-pagination ds-pagination" aria-label="Paginação dos resultados">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'total'     => (int) $q->max_num_pages,
									'current'   => $paged,
									'prev_text' => '&lsaquo;',
									'next_text' => '&rsaquo;',
									'type'      => 'list',
								)
							)
						);
						?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="ds-empty-box">
					<h3>Nenhum produto encontrado</h3>
					<p>Não localizamos itens com os critérios informados. Tente buscar outro termo ou navegue pelos nossos departamentos.</p>
					<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-btn ds-btn-dark">Ver Catálogo Completo</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

add_shortcode(
	'store_product_search_page',
	function (): string {
		$parsed = store_parse_active_filter_params();
		return store_render_search_results_markup( $parsed['search'] );
	}
);
