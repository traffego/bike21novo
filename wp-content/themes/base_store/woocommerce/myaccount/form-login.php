<?php
/**
 * Formulário de Login e Cadastro de Clientes na área Minha Conta.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reg_enabled     = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

do_action( 'woocommerce_before_customer_login_form' );
?>

<?php if ( $reg_enabled ) : ?>
	<div class="u-columns col2-set ds-login-register-grid" id="customer_login">
		<div class="u-column1 col-1">
<?php else : ?>
	<div class="ds-login-single-card" id="customer_login">
<?php endif; ?>

		<h2>Entrar na Conta</h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username">Usuário ou e-mail&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password">Senha&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span>Manter conectado</span>
				</label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( $wp_button_class ); ?>" name="login" value="Entrar">Entrar</button>
			</p>
			<p class="woocommerce-LostPassword lost_password" style="margin-bottom:0;">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Esqueceu sua senha?</a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>

<?php if ( $reg_enabled ) : ?>
		</div>

		<div class="u-column2 col-2">
			<h2>Criar Nova Conta</h2>

			<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username">Nome de usuário&nbsp;<span class="required" aria-hidden="true">*</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email">Endereço de e-mail&nbsp;<span class="required" aria-hidden="true">*</span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" />
				</p>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password">Senha&nbsp;<span class="required" aria-hidden="true">*</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>
				<?php else : ?>
					<p style="font-size:12px;color:var(--ds-slate-500);">Um link para definir sua nova senha será enviado para o endereço de e-mail informado.</p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row" style="margin-bottom:0;">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( $wp_button_class ); ?> woocommerce-form-register__submit" name="register" value="Cadastrar">Criar Conta</button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>
	</div>
<?php else : ?>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
