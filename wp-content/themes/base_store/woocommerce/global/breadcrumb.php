<?php
/**
 * Caminho de navegação (Breadcrumb) agnóstico do catálogo e produto.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $breadcrumb ) ) :
	?>
	<div class="ds-breadcrumb-bar">
		<div class="ds-container">
			<nav class="ds-breadcrumb" aria-label="Caminho de navegação">
				<?php foreach ( $breadcrumb as $key => $crumb ) : ?>
					<?php if ( $key > 0 ) : ?>
						<span class="ds-breadcrumb__sep" aria-hidden="true">/</span>
					<?php endif; ?>
					<?php if ( ! empty( $crumb[1] ) && count( $breadcrumb ) !== $key + 1 ) : ?>
						<a href="<?php echo esc_url( $crumb[1] ); ?>"><?php echo esc_html( $crumb[0] ); ?></a>
					<?php else : ?>
						<span class="ds-breadcrumb__current"><?php echo esc_html( $crumb[0] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
		</div>
	</div>
	<?php
endif;
