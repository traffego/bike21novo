<?php
/**
 * Template principal do Arquivo de Loja, Categorias e Busca de Produtos.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>
<main id="content" class="ds-wc-main" role="main">
	<?php
	woocommerce_breadcrumb();

	if ( is_search() ) :
		?>
		<div class="ds-page-shell">
			<div class="ds-container">
				<?php wc_print_notices(); ?>
				<?php echo store_render_search_results_markup( get_search_query() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	else :
		$term_obj     = is_product_taxonomy() ? get_queried_object() : null;
		$hero_eyebrow = is_product_category() ? 'Categoria Especializada' : 'Catálogo Oficial';
		if ( $term_obj instanceof WP_Term && $term_obj->parent > 0 ) {
			$parent_term = get_term( $term_obj->parent, 'product_cat' );
			if ( $parent_term instanceof WP_Term ) {
				$hero_eyebrow = $parent_term->name;
			}
		}

		$prep_badge     = store_term( 'prep_badge_label' );
		$hero_desc      = '';
		if ( $term_obj instanceof WP_Term && '' !== trim( (string) $term_obj->description ) ) {
			$hero_desc = wpautop( $term_obj->description );
		} else {
			$tagline   = store_term( 'store_tagline' );
			$hero_desc = '' !== $tagline ? '<p>' . esc_html( $tagline ) . '</p>' : '';
		}
		$subcats        = store_get_catalog_subcategories();
		$adv_badge      = store_term( 'store_advantages_badge' );
		$adv_title      = store_term( 'store_advantages_title' );
		$adv_desc       = store_term( 'store_advantages_desc' );
		$wa_url         = Store_Glossary::whatsapp_link();
		$delivery_page  = get_page_by_path( 'entrega-e-montagem' );
		$delivery_url   = $delivery_page ? get_permalink( $delivery_page->ID ) : '';
		?>
		<header class="ds-archive-hero">
			<div class="ds-container">
				<div class="ds-archive-hero__inner">
					<div class="ds-archive-hero__meta">
						<span class="ds-pill-badge ds-pill-badge--amber"><?php echo esc_html( $hero_eyebrow ); ?></span>
						<?php if ( '' !== $prep_badge ) : ?>
							<span class="ds-archive-hero__prep">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
								<?php echo esc_html( $prep_badge ); ?>
							</span>
						<?php endif; ?>
					</div>

					<h1 class="ds-archive-hero__title"><?php echo esc_html( woocommerce_page_title( false ) ); ?></h1>

					<?php if ( '' !== $hero_desc ) : ?>
						<div class="ds-archive-hero__desc"><?php echo wp_kses_post( $hero_desc ); ?></div>
					<?php endif; ?>

					<?php if ( ! empty( $subcats ) ) : ?>
						<nav class="ds-archive-subcats" aria-label="Subcategorias relacionadas">
							<?php foreach ( $subcats as $subcat ) : ?>
								<a href="<?php echo esc_url( $subcat['url'] ); ?>" class="ds-subcat-pill<?php echo ! empty( $subcat['active'] ) ? ' is-active' : ''; ?>">
									<?php echo esc_html( $subcat['name'] ); ?>
								</a>
							<?php endforeach; ?>
						</nav>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<div class="ds-page-shell">
			<div class="ds-container">
				<?php wc_print_notices(); ?>

				<div id="storeFilterBackdrop" class="ds-drawer-backdrop" hidden></div>

				<div class="ds-catalog-layout">
					<aside id="storeCatalogSidebar" class="ds-catalog-sidebar" aria-label="Filtros do catálogo">
						<div class="ds-drawer-header-mobile">
							<strong>Filtrar Produtos</strong>
							<button type="button" id="storeFilterDrawerClose" class="ds-btn ds-btn-soft ds-btn-sm">&times; Fechar</button>
						</div>
						<?php echo store_render_catalog_sidebar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</aside>

					<section class="ds-catalog-main" aria-label="Lista de produtos">
						<div class="ds-catalog-toolbar">
							<div><?php woocommerce_result_count(); ?></div>
							<div class="ds-catalog-toolbar__right">
								<button type="button" id="storeFilterDrawerBtn" class="ds-btn ds-btn-outline ds-mobile-filter-toggle">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
									Filtrar
								</button>
								<?php woocommerce_catalog_ordering(); ?>
							</div>
						</div>

						<?php if ( woocommerce_product_loop() ) : ?>
							<div class="ds-product-grid ds-cols-3">
								<?php
								if ( wc_get_loop_prop( 'total' ) ) {
									while ( have_posts() ) {
										the_post();
										do_action( 'woocommerce_shop_loop' );
										wc_get_template_part( 'content', 'product' );
									}
								}
								?>
							</div>
							<?php woocommerce_pagination(); ?>
						<?php else : ?>
							<div class="ds-empty-box">
								<div class="ds-empty-box__icon" aria-hidden="true">
									<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
								</div>
								<h3>Nenhum produto corresponde a esses filtros</h3>
								<p>Tente remover alguns critérios ou limpar os filtros ativos para visualizar todos os itens deste departamento.</p>
								<a href="<?php echo esc_url( store_get_catalog_base_url() ); ?>" class="ds-btn ds-btn-dark">Limpar Todos os Filtros</a>
							</div>
						<?php endif; ?>
					</section>
				</div>

				<?php if ( '' !== $adv_title ) : ?>
					<section class="ds-advantages-banner" aria-label="Diferenciais de preparo e envio">
						<div>
							<?php if ( '' !== $adv_badge ) : ?>
								<span class="ds-pill-badge ds-pill-badge--dark"><?php echo esc_html( $adv_badge ); ?></span>
							<?php endif; ?>
							<h2 class="ds-advantages-banner__title"><?php echo esc_html( $adv_title ); ?></h2>
							<?php if ( '' !== $adv_desc ) : ?>
								<p class="ds-advantages-banner__desc"><?php echo esc_html( $adv_desc ); ?></p>
							<?php endif; ?>
						</div>
						<div class="ds-advantages-banner__cta">
							<?php if ( '' !== $delivery_url ) : ?>
								<a href="<?php echo esc_url( $delivery_url ); ?>" class="ds-btn ds-btn-outline">Ver Envio &amp; Preparação</a>
							<?php endif; ?>
							<?php if ( '' !== $wa_url ) : ?>
								<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-amber">Falar com Consultor</a>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>
		</div>
		<?php
	endif;
	?>
</main>
<?php
get_footer( 'shop' );
