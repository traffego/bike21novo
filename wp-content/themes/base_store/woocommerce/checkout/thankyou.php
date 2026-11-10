<?php
/**
 * Página de Pedido Confirmado (Thank You) + Suporte ao shortcode `[store_order_confirmation]`.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.2.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order ds-thankyou-wrap">
	<div class="ds-funnel-wrap">
		<?php echo store_render_funnel_steps( 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<?php if ( $order instanceof WC_Order ) : ?>
		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>
			<?php
			$default_failed_text = 'Infelizmente o pagamento não pôde ser processado pela instituição financeira. Tente novamente ou escolha outra forma de pagamento.';
			$order_failed_text   = apply_filters( 'woocommerce_thankyou_order_failed_text', $default_failed_text, $order );
			$order_failed_text   = is_string( $order_failed_text ) ? $order_failed_text : $default_failed_text;
			?>
			<div class="ds-panel-card ds-thankyou-hero">
				<div class="ds-empty-box__icon" style="background-color:var(--ds-rose-50);color:var(--ds-rose-600);" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
				</div>
				<h1 class="ds-thankyou-hero__title">Pagamento não autorizado</h1>
				<p class="ds-thankyou-hero__subtitle"><?php echo wp_kses_post( $order_failed_text ); ?></p>
				<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:8px;">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="ds-btn ds-btn-amber">Tentar Pagamento Novamente</a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="ds-btn ds-btn-soft">Minha Conta</a>
					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>
			<?php
			$order_number   = $order->get_order_number();
			$order_date     = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'd/m/Y H:i' ) : '';
			$payment_method = $order->get_payment_method();
			$payment_title  = $order->get_payment_method_title();
			$is_pix_pm      = store_is_pix_gateway( $payment_method );
			$pix_key        = trim( store_term( 'pix_key' ) );
			$show_pix_panel = $is_pix_pm
				&& ( $order->needs_payment() || in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) )
				&& '' !== $pix_key;

			$timeline      = store_build_order_timeline( $order );
			$tracking_code = store_get_order_tracking_code( $order );
			$track_page    = get_page_by_path( 'rastreio' );
			$track_url     = $track_page
				? add_query_arg(
					array(
						'pedido' => $order_number,
						'email'  => $order->get_billing_email(),
					),
					get_permalink( $track_page->ID )
				)
				: '';

			$wa_link = Store_Glossary::whatsapp_link(
				trim( ( store_term( 'whatsapp_order_prefix' ) ?: 'Olá! Preciso de ajuda com o pedido #' ) . $order_number )
			);

			$person_type = (string) $order->get_meta( '_billing_person_type' );
			$is_pj       = 'pj' === $person_type;
			?>

			<div class="ds-panel-card ds-thankyou-hero">
				<div class="ds-thankyou-hero__icon" aria-hidden="true">
					<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
				</div>
				<span class="ds-pill-badge ds-pill-badge--emerald">
					<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
				</span>
				<h1 class="ds-thankyou-hero__title">Pedido #<?php echo esc_html( $order_number ); ?> Confirmado!</h1>
				<p class="ds-thankyou-hero__subtitle">
					Obrigado pela sua compra<?php echo $order->get_billing_first_name() ? ', ' . esc_html( $order->get_billing_first_name() ) : ''; ?>.
					Enviamos a confirmação e o acompanhamento detalhado para <strong><?php echo esc_html( $order->get_billing_email() ); ?></strong>.
				</p>

				<div class="ds-summary-grid-4">
					<div class="ds-summary-mini-box">
						<span>Número do Pedido</span>
						<strong>#<?php echo esc_html( $order_number ); ?></strong>
					</div>
					<div class="ds-summary-mini-box">
						<span>Data do Pedido</span>
						<strong><?php echo esc_html( $order_date ); ?></strong>
					</div>
					<div class="ds-summary-mini-box">
						<span>Pagamento</span>
						<strong><?php echo esc_html( $payment_title ?: 'Confirmado' ); ?></strong>
					</div>
					<div class="ds-summary-mini-box">
						<span>Valor Total</span>
						<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
					</div>
				</div>
			</div>

			<?php if ( $show_pix_panel ) : ?>
				<?php
				$pix_brcode = store_generate_pix_brcode(
					$pix_key,
					(float) $order->get_total(),
					store_term( 'pix_beneficiary' ),
					store_term( 'pix_city' ),
					'PED' . $order->get_id()
				);
				$pix_svg = '' !== $pix_brcode ? store_render_pix_matrix_svg( $pix_brcode ) : '';
				?>
				<div class="ds-pix-panel">
					<div class="ds-pix-panel__head">
						<div>
							<span class="ds-eyebrow" style="color:var(--ds-amber-700);">Pagamento Instantâneo</span>
							<h2 class="ds-section-title" style="font-size:1.15rem;">Pague via PIX para liberar seu envio imediatamente</h2>
						</div>
						<div class="ds-pix-timer-pill">
							Validade: <span id="storePixTimer" data-minutes="15">15:00</span>
						</div>
					</div>

					<div class="ds-pix-panel__body">
						<div class="ds-pix-qr-box">
							<?php echo $pix_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<strong style="font-size:12px;color:var(--ds-zinc-900);"><?php echo esc_html( store_format_brl( (float) $order->get_total() ) ); ?></strong>
						</div>

						<div>
							<ol class="ds-pix-steps-list">
								<li>
									<span class="ds-pix-step-num">1</span>
									<span>Abra o aplicativo do seu banco e acesse a área <strong>PIX</strong>.</span>
								</li>
								<li>
									<span class="ds-pix-step-num">2</span>
									<span>Escolha <strong>Ler QR Code</strong> ou utilize o código <strong>PIX Copia e Cola</strong> abaixo.</span>
								</li>
								<li>
									<span class="ds-pix-step-num">3</span>
									<span>Confira o favorecido (<strong><?php echo esc_html( store_term( 'pix_beneficiary' ) ?: store_term( 'store_name' ) ); ?></strong>) e confirme o pagamento.</span>
								</li>
							</ol>

							<?php if ( '' !== $pix_brcode ) : ?>
								<div class="ds-pix-brcode-row">
									<label for="storePixCode" class="screen-reader-text">Código PIX Copia e Cola</label>
									<input id="storePixCode" type="text" readonly value="<?php echo esc_attr( $pix_brcode ); ?>" />
									<button type="button" id="storePixCopyBtn" class="ds-btn ds-btn-dark">Copiar Código PIX</button>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="ds-panel-card">
				<div class="ds-card-head">
					<h2 class="ds-card-head__title">Status &amp; Acompanhamento</h2>
					<?php if ( '' !== $tracking_code ) : ?>
						<span class="ds-pill-badge ds-pill-badge--emerald">Rastreio: <?php echo esc_html( $tracking_code ); ?></span>
					<?php endif; ?>
				</div>

				<div class="ds-timeline">
					<?php foreach ( $timeline as $step ) : ?>
						<div class="ds-timeline__step ds-timeline__step--<?php echo esc_attr( $step['state'] ); ?>">
							<div class="ds-timeline__dot" aria-hidden="true"></div>
							<div>
								<strong class="ds-timeline__title"><?php echo esc_html( $step['title'] ); ?></strong>
								<p class="ds-timeline__desc"><?php echo esc_html( $step['desc'] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="ds-thankyou-details-grid">
				<div class="ds-panel-card">
					<div class="ds-card-head">
						<h3 class="ds-card-head__title">Itens do Pedido</h3>
					</div>

					<div class="ds-thankyou-items-list">
						<?php
						foreach ( $order->get_items() as $item_id => $item ) :
							if ( ! $item instanceof WC_Order_Item_Product ) {
								continue;
							}
							$item_product = $item->get_product();
							$thumb        = $item_product instanceof WC_Product ? $item_product->get_image( 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img( 'woocommerce_gallery_thumbnail' );
							?>
							<div class="ds-thankyou-item">
								<div class="ds-thankyou-item__thumb">
									<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div class="ds-thankyou-item__info">
									<div class="ds-thankyou-item__title">
										<?php echo esc_html( $item->get_name() ); ?> &times; <?php echo esc_html( $item->get_quantity() ); ?>
									</div>
									<?php
									$formatted_meta = wc_display_item_meta( $item, array( 'echo' => false ) );
									if ( $formatted_meta ) {
										echo '<div class="ds-thankyou-item__meta">' . wp_kses_post( wp_strip_all_tags( $formatted_meta ) ) . '</div>';
									}
									?>
								</div>
								<div class="ds-thankyou-item__price">
									<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<table class="shop_table ds-summary-table">
						<tbody>
							<?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
								<tr class="<?php echo 'order_total' === $key ? 'order-total' : ''; ?>">
									<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
									<td><?php echo wp_kses_post( $total['value'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="ds-panel-card">
					<div class="ds-card-head">
						<h3 class="ds-card-head__title">Dados Fiscais &amp; Entrega</h3>
					</div>

					<div class="ds-thankyou-meta-list">
						<div>
							<strong>Cliente / Faturamento (NF-e):</strong><br />
							<?php echo wp_kses_post( $order->get_formatted_billing_address() ); ?>
							<?php if ( $is_pj ) : ?>
								<br />CNPJ: <strong><?php echo esc_html( (string) $order->get_meta( '_billing_cnpj' ) ); ?></strong>
								<?php
								$ie_display = 'yes' === $order->get_meta( '_billing_ie_exempt' ) ? 'Isento' : (string) $order->get_meta( '_billing_ie' );
								if ( '' !== $ie_display ) {
									echo ' • IE: ' . esc_html( $ie_display );
								}
								if ( $order->get_meta( '_billing_responsible_name' ) ) {
									echo '<br />Responsável: ' . esc_html( (string) $order->get_meta( '_billing_responsible_name' ) );
								}
								?>
							<?php elseif ( $order->get_meta( '_billing_cpf' ) ) : ?>
								<br />CPF: <strong><?php echo esc_html( (string) $order->get_meta( '_billing_cpf' ) ); ?></strong>
							<?php endif; ?>
							<?php if ( $order->get_billing_phone() ) : ?>
								<br />Telefone: <?php echo esc_html( $order->get_billing_phone() ); ?>
							<?php endif; ?>
						</div>

						<?php if ( $order->has_shipping_address() ) : ?>
							<div style="padding-top:12px;border-top:1px solid var(--ds-slate-100);">
								<strong>Endereço de Entrega:</strong><br />
								<?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?>
								<?php if ( $order->get_shipping_method() ) : ?>
									<br />Envio: <strong><?php echo esc_html( $order->get_shipping_method() ); ?></strong>
								<?php endif; ?>

							</div>
						<?php endif; ?>

						<?php if ( $order->get_customer_note() ) : ?>
							<div style="padding-top:12px;border-top:1px solid var(--ds-slate-100);">
								<strong>Observações:</strong><br />
								<?php echo esc_html( $order->get_customer_note() ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="ds-thankyou-actions no-print">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/loja/' ) ); ?>" class="ds-btn ds-btn-soft">
					&larr; Continuar Comprando
				</a>

				<div class="ds-thankyou-actions__right">
					<button type="button" onclick="window.print()" class="ds-btn ds-btn-outline">
						Imprimir Comprovante
					</button>
					<?php if ( '' !== $track_url ) : ?>
						<a href="<?php echo esc_url( $track_url ); ?>" class="ds-btn ds-btn-dark">
							Rastrear Pedido
						</a>
					<?php endif; ?>
					<?php if ( '' !== $wa_link ) : ?>
						<a href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-whatsapp">
							Falar no WhatsApp
						</a>
					<?php endif; ?>
				</div>
			</div>

		<?php endif; ?>

		<?php
		// Evita duplicação da tabela BACS padrão do WooCommerce quando BACS está configurado como fluxo PIX.
		if ( 'bacs' === $order->get_payment_method() && store_is_pix_gateway( 'bacs' ) ) {
			global $wp_filter;
			if ( isset( $wp_filter['woocommerce_thankyou_bacs'] ) ) {
				remove_all_actions( 'woocommerce_thankyou_bacs' );
			}
		}

		// Evita segunda renderização completa de tabela padrão abaixo do card moderno.
		if ( has_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' ) ) {
			remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
		}

		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
		?>

	<?php else : ?>
		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
	<?php endif; ?>
</div>
