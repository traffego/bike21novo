<?php
/**
 * Formulário de Checkout Brasileiro (2 Colunas: PF/PJ + Endereço + Pagamento na col 1; Resumo sticky na col 2).
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ds-funnel-wrap">
	<?php echo store_render_funnel_steps( 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<?php
do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', 'Você precisa estar conectado para finalizar a compra.' ) );
	return;
}

$trust_pay  = store_term( 'payment_gateway_label' );
$trust_prep = store_term( 'prep_badge_label' );
$trust_sec  = store_term( 'security_badge' );
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="Finalizar Compra">

	<div class="ds-checkout-layout">
		<div class="ds-checkout-main" id="customer_details">
			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div class="ds-checkout-section-card">
					<div class="ds-checkout-section-head">
						<h2>
							<span class="ds-step-num">1</span>
							Identificação, Dados Fiscais (PF/PJ) &amp; Faturamento
						</h2>
						<span class="ds-pill-badge ds-pill-badge--emerald">NF-e Garantida</span>
					</div>
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
				</div>

				<div class="ds-checkout-section-card">
					<div class="ds-checkout-section-head">
						<h2>
							<span class="ds-step-num">2</span>
							Endereço de Entrega &amp; Observações
						</h2>
					</div>
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			<?php endif; ?>

			<div class="ds-checkout-section-card ds-checkout-payment-card">
				<div class="ds-checkout-section-head">
					<h2>
						<span class="ds-step-num">3</span>
						Forma de Pagamento
					</h2>
					<?php if ( '' !== store_term( 'pix_discount_badge' ) ) : ?>
						<span class="ds-pill-badge ds-pill-badge--amber-soft"><?php echo esc_html( store_term( 'pix_discount_badge' ) ); ?></span>
					<?php endif; ?>
				</div>

				<?php
				if ( has_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment' ) ) {
					remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
				}
				woocommerce_checkout_payment();
				?>
			</div>
		</div>

		<aside class="ds-checkout-sidebar">
			<div class="ds-cart-totals-card">
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

				<h3 id="order_review_heading" class="ds-checkout-review-title">
					<span>Resumo do Pedido</span>
				</h3>

				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

				<?php if ( '' !== $trust_pay || '' !== $trust_prep || '' !== $trust_sec ) : ?>
					<div class="ds-trust-list">
						<?php foreach ( array_filter( array( $trust_pay, $trust_prep, $trust_sec ) ) as $trust_item ) : ?>
							<div class="ds-trust-list__item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
								<span><?php echo esc_html( $trust_item ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</aside>
	</div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
