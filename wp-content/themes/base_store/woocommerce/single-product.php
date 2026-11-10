<?php
/**
 * Template externo da Página de Produto (PDP).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );
?>
<main id="content" class="ds-wc-main" role="main">
	<?php woocommerce_breadcrumb(); ?>
	<div class="ds-page-shell">
		<div class="ds-container">
			<?php
			while ( have_posts() ) :
				the_post();
				wc_get_template_part( 'content', 'single-product' );
			endwhile;
			?>
		</div>
	</div>
</main>
<?php
get_footer( 'shop' );
