<?php
/**
 * Formulário de Checkout Multi-Step de Alta Conversão (Informações → Envio → Pagamento).
 * 100% em português brasileiro, compatível com WooCommerce, Melhor Envio e Mercado Pago.
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

<form name="checkout" method="post" class="checkout woocommerce-checkout ds-multi-step-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="Finalizar Compra">

	<div class="ds-checkout-layout">
		<div class="ds-checkout-main" id="customer_details">

			<!-- 1. Stepper Interativo Superior (3 Etapas) -->
			<nav class="ds-checkout-stepper" id="ds-checkout-stepper" aria-label="Etapas de Checkout">
				<div class="ds-checkout-stepper__item is-active" data-step-target="1">
					<span class="ds-checkout-stepper__circle">1</span>
					<span class="ds-checkout-stepper__label">Informações</span>
				</div>
				<div class="ds-checkout-stepper__line" data-line="1"></div>
				<div class="ds-checkout-stepper__item is-pending" data-step-target="2">
					<span class="ds-checkout-stepper__circle">2</span>
					<span class="ds-checkout-stepper__label">Envio</span>
				</div>
				<div class="ds-checkout-stepper__line" data-line="2"></div>
				<div class="ds-checkout-stepper__item is-pending" data-step-target="3">
					<span class="ds-checkout-stepper__circle">3</span>
					<span class="ds-checkout-stepper__label">Pagamento</span>
				</div>
			</nav>

			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<!-- ETAPA 1: Informações (Contato, Dados Fiscais PF/PJ e Endereço de Entrega) -->
				<div class="ds-step-pane is-active" data-step-pane="1">
					<div class="ds-checkout-section-card">
						<div class="ds-checkout-section-head">
							<h2>
								<span class="ds-step-num">1</span>
								Contato &amp; Dados Fiscais
							</h2>
							<span class="ds-pill-badge ds-pill-badge--emerald">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
								NF-e Garantida
							</span>
						</div>
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>

					<div class="ds-checkout-section-card" style="margin-top: 18px;">
						<div class="ds-checkout-section-head">
							<h2>
								<span class="ds-step-num">2</span>
								Endereço de Entrega
							</h2>
							<span class="ds-pill-badge ds-pill-badge--cyan">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
								Preenchimento por CEP
							</span>
						</div>
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>

					<div class="ds-step-footer-action">
						<button type="button" class="ds-btn ds-btn-primary ds-btn--next-step" data-next-step="2">
							<span>Continuar para o Envio</span>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
						</button>
					</div>
				</div>

				<!-- ETAPA 2: Envio (Recap do Passo 1 + Seleção de Opção de Frete) -->
				<div class="ds-step-pane" data-step-pane="2" style="display: none;">
					<div class="ds-checkout-section-card">
						<!-- Recap Box do Passo 1 -->
						<div class="ds-recap-box" id="ds-recap-box-step2">
							<div class="ds-recap-box__row">
								<div class="ds-recap-box__label">Contato</div>
								<div class="ds-recap-box__value" id="ds-recap-contact-val">—</div>
								<button type="button" class="ds-recap-box__btn" data-goto-step="1">Alterar</button>
							</div>
							<div class="ds-recap-box__row">
								<div class="ds-recap-box__label">Entregar em</div>
								<div class="ds-recap-box__value" id="ds-recap-address-val">—</div>
								<button type="button" class="ds-recap-box__btn" data-goto-step="1">Alterar</button>
							</div>
						</div>

						<div class="ds-checkout-section-head" style="margin-top: 24px;">
							<h2>
								<span class="ds-step-num">3</span>
								Método de Envio
							</h2>
							<span class="ds-pill-badge ds-pill-badge--cyan">Seguro Total</span>
						</div>

						<p class="ds-checkout-subdesc">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
							<span>Selecione a opção de entrega de sua preferência para o seu endereço:</span>
						</p>

						<div class="ds-step2-shipping-methods" id="ds-step2-shipping-mount">
							<!-- As opções de frete reais do WooCommerce / Melhor Envio são espelhadas interativamente aqui -->
						</div>
					</div>

					<div class="ds-step-footer-action ds-step-footer-action--between">
						<button type="button" class="ds-step-back-link" data-goto-step="1">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
							<span>Voltar para Informações</span>
						</button>
						<button type="button" class="ds-btn ds-btn-primary ds-btn--next-step" data-next-step="3">
							<span>Continuar para o Pagamento</span>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
						</button>
					</div>
				</div>

				<!-- ETAPA 3: Pagamento (Recap Completo + Mercado Pago Transparente) -->
				<div class="ds-step-pane" data-step-pane="3" style="display: none;">
					<div class="ds-checkout-section-card ds-checkout-payment-card">
						<!-- Recap Box dos Passos 1 e 2 -->
						<div class="ds-recap-box" id="ds-recap-box-step3">
							<div class="ds-recap-box__row">
								<div class="ds-recap-box__label">Contato</div>
								<div class="ds-recap-box__value" id="ds-recap-contact-step3-val">—</div>
								<button type="button" class="ds-recap-box__btn" data-goto-step="1">Alterar</button>
							</div>
							<div class="ds-recap-box__row">
								<div class="ds-recap-box__label">Entregar em</div>
								<div class="ds-recap-box__value" id="ds-recap-address-step3-val">—</div>
								<button type="button" class="ds-recap-box__btn" data-goto-step="1">Alterar</button>
							</div>
							<div class="ds-recap-box__row">
								<div class="ds-recap-box__label">Método de Envio</div>
								<div class="ds-recap-box__value" id="ds-recap-shipping-step3-val">—</div>
								<button type="button" class="ds-recap-box__btn" data-goto-step="2">Alterar</button>
							</div>
						</div>

						<div class="ds-checkout-section-head" style="margin-top: 24px;">
							<h2>
								<span class="ds-step-num">4</span>
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

					<div class="ds-step-footer-action ds-step-footer-action--between" style="margin-top: 18px;">
						<button type="button" class="ds-step-back-link" data-goto-step="2">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
							<span>Voltar para Envio</span>
						</button>
						<div></div>
					</div>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			<?php endif; ?>

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
