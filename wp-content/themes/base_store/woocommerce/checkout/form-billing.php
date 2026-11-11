<?php
/**
 * Checkout billing information form (Passo 1: Identificação, Contato & Dados Fiscais PF/PJ).
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="ds-checkout-subdesc">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
		<span>Ambiente 100% seguro. Seus dados são criptografados e utilizados exclusivamente para faturamento fiscal e envio.</span>
	</div>

	<div class="woocommerce-billing-fields__field-wrapper ds-billing-contact-grid">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		// Renderiza exclusivamente os campos de contato e fiscais no Passo 1
		$step1_keys = array(
			'billing_email',
			'billing_phone',
			'billing_person_type',
			'billing_first_name',
			'billing_last_name',
			'billing_cpf',
			'billing_birthdate',
			'billing_cnpj',
			'billing_company',
			'billing_ie',
			'billing_ie_exempt',
			'billing_responsible_name',
			'billing_trade_name',
		);

		foreach ( $step1_keys as $key ) {
			if ( isset( $fields[ $key ] ) ) {
				woocommerce_form_field( $key, $fields[ $key ], $checkout->get_value( $key ) );
			}
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
