<?php
/**
 * Listagem de Endereços Cadastrados na área Minha Conta.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => 'Endereço de Cobrança',
			'shipping' => 'Endereço de Entrega',
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => 'Endereço de Cobrança',
		),
		$customer_id
	);
}

$oldcol = 1;
$col    = 1;
?>

<p class="ds-section-subtitle" style="margin:0 0 14px;">
	<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html( 'Os endereços abaixo serão preenchidos automaticamente nas próximas compras.' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</p>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	<div class="u-columns woocommerce-Addresses col2-set addresses">
<?php endif; ?>

<?php foreach ( $get_addresses as $name => $address_title ) : ?>
	<?php
	$address = wc_get_account_formatted_address( $name );
	$col     = $col * -1;
	$oldcol  = $oldcol * -1;
	?>

	<div class="u-column<?php echo $col < 0 ? 1 : 2; ?> col-<?php echo $oldcol < 0 ? 1 : 2; ?> woocommerce-Address">
		<header class="woocommerce-Address-title title">
			<h2><?php echo esc_html( $address_title ); ?></h2>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit">
				<?php echo $address ? 'Editar' : 'Adicionar'; ?>
			</a>
		</header>
		<address>
			<?php
			echo $address ? wp_kses_post( $address ) : esc_html( 'Você ainda não configurou este endereço.' );
			do_action( 'woocommerce_my_account_after_my_address', $name );
			?>
		</address>
	</div>

<?php endforeach; ?>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	</div>
<?php endif; ?>
