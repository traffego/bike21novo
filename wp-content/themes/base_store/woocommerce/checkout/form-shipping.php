<?php
/**
 * Checkout shipping information form (Passo 2: Endereço de Entrega por CEP + Opção de Endereço Alternativo + Observações).
 *
 * @package WooCommerce\Templates
 * @version 11.2.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-shipping-fields ds-checkout-delivery-section">
	<div class="ds-checkout-subdesc">
		<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
		<span>Digite seu CEP para preenchimento instantâneo. Envio protegido com seguro total e rastreamento.</span>
	</div>

	<div class="ds-primary-address-wrapper">
		<?php
		$billing_fields = $checkout->get_checkout_fields( 'billing' );

		// Renderiza os campos de endereço principal no Passo 2
		$address_keys = array(
			'billing_country',
			'billing_postcode',
			'billing_address_1',
			'billing_number',
			'billing_address_2',
			'billing_neighborhood',
			'billing_city',
			'billing_state',
		);

		foreach ( $address_keys as $key ) {
			if ( isset( $billing_fields[ $key ] ) ) {
				woocommerce_form_field( $key, $billing_fields[ $key ], $checkout->get_value( $key ) );
			}
		}
		?>
		<div class="ds-clear" style="clear: both;"></div>
	</div>

	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>
		<div class="ds-ship-to-different-wrap" id="ship-to-different-address">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox ds-diff-toggle-label" for="ship-to-different-address-checkbox">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" />
				<span>Quero receber em um endereço diferente da Nota Fiscal</span>
			</label>

			<div class="shipping_address ds-shipping-different-fields">
				<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

				<div class="woocommerce-shipping-fields__field-wrapper">
					<?php
					$shipping_fields = $checkout->get_checkout_fields( 'shipping' );
					foreach ( $shipping_fields as $key => $field ) {
						woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
					}
					?>
					<div class="ds-clear" style="clear: both;"></div>
				</div>

				<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<div class="woocommerce-additional-fields ds-additional-notes-wrap">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
