<?php
/**
 * Container principal de Minha Conta com cabeçalho de perfil e menu lateral.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$display_name = trim( (string) $current_user->display_name ) ?: trim( (string) $current_user->user_login );
$name_parts   = preg_split( '/\s+/', $display_name );
$initials     = '';
if ( is_array( $name_parts ) && ! empty( $name_parts[0] ) ) {
	$initials .= strtoupper( mb_substr( $name_parts[0], 0, 1 ) );
	if ( count( $name_parts ) > 1 ) {
		$initials .= strtoupper( mb_substr( end( $name_parts ), 0, 1 ) );
	} elseif ( mb_strlen( $name_parts[0] ) > 1 ) {
		$initials .= strtoupper( mb_substr( $name_parts[0], 1, 1 ) );
	}
}
if ( '' === $initials ) {
	$initials = 'CL';
}

$user_cpf = get_user_meta( $current_user->ID, 'billing_cpf', true );
if ( ! $user_cpf ) {
	$user_cpf = get_user_meta( $current_user->ID, '_billing_cpf', true );
}
?>

<div class="ds-myaccount-wrap">
	<?php if ( is_user_logged_in() ) : ?>
		<div class="ds-panel-card ds-profile-header">
			<div class="ds-profile-header__left">
				<div class="ds-profile-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></div>
				<div>
					<h1 class="ds-profile-name">Olá, <?php echo esc_html( $display_name ); ?></h1>
					<p class="ds-profile-meta">
						<?php echo esc_html( $current_user->user_email ); ?>
						<?php if ( $user_cpf ) : ?>
							&bull; CPF: <?php echo esc_html( (string) $user_cpf ); ?>
						<?php endif; ?>
					</p>
					<span class="ds-pill-badge ds-pill-badge--emerald" style="margin-top:6px;">Conta Verificada</span>
				</div>
			</div>

			<a href="<?php echo esc_url( wc_logout_url() ); ?>" class="ds-btn ds-btn-rose-soft">
				Sair da Conta
			</a>
		</div>
	<?php endif; ?>

	<div class="ds-myaccount-grid">
		<?php do_action( 'woocommerce_account_navigation' ); ?>

		<div class="woocommerce-MyAccount-content">
			<?php do_action( 'woocommerce_account_content' ); ?>
		</div>
	</div>
</div>
