<?php
/**
 * Edição de Endereço de Cobrança ou Entrega na área Minha Conta.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$page_title      = ( 'billing' === $load_address ) ? 'Endereço de Cobrança' : 'Endereço de Entrega';
$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

do_action( 'woocommerce_before_edit_account_address_form' );
?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>
	<div class="ds-panel-card ds-account-form-wrap">
		<form method="post" novalidate>
			<h2><?php echo apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>

			<div class="woocommerce-address-fields">
				<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

				<div class="woocommerce-address-fields__field-wrapper">
					<?php
					foreach ( $address as $key => $field ) {
						woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
					}
					?>
				</div>

				<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

				<p style="margin-bottom:0;">
					<button type="submit" class="woocommerce-Button button<?php echo esc_attr( $wp_button_class ); ?>" name="save_address" value="Salvar endereço">Salvar Endereço</button>
					<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
					<input type="hidden" name="action" value="edit_address" />
				</p>
			</div>
		</form>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
