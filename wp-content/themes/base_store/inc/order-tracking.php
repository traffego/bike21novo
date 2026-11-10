<?php
/**
 * Shortcode de rastreamento real de pedidos WooCommerce (`[store_order_tracking]`).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_get_order_tracking_code' ) ) {
	/**
	 * Lê o código de rastreio salvo em metadados padrão do pedido.
	 *
	 * @param WC_Order $order Pedido WooCommerce.
	 * @return string
	 */
	function store_get_order_tracking_code( WC_Order $order ): string {
		foreach ( array( '_tracking_code', '_wc_shipment_tracking_items', '_correios_tracking_code', '_melhor_envio_tracking' ) as $meta_key ) {
			$val = $order->get_meta( $meta_key );
			if ( is_string( $val ) && '' !== trim( $val ) ) {
				return trim( $val );
			}
			if ( is_array( $val ) && ! empty( $val[0]['tracking_number'] ) ) {
				return (string) $val[0]['tracking_number'];
			}
		}
		return '';
	}
}

if ( ! function_exists( 'store_build_order_timeline' ) ) {
	/**
	 * Monta etapas dinâmicas da linha do tempo conforme status real do pedido no WooCommerce.
	 *
	 * @param WC_Order $order Pedido WooCommerce.
	 * @return array<int,array{title:string,desc:string,state:string}>
	 */
	function store_build_order_timeline( WC_Order $order ): array {
		$status       = $order->get_status();
		$created_at   = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'd/m/Y \à\s H:i' ) : '';
		$paid_at      = $order->get_date_paid() ? wc_format_datetime( $order->get_date_paid(), 'd/m/Y \à\s H:i' ) : '';
		$tracking     = store_get_order_tracking_code( $order );
		$shipping_ttl = $order->get_shipping_method();

		if ( in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			return array(
				array(
					'title' => 'Pedido Registrado',
					'desc'  => $created_at ? 'Registrado em ' . $created_at : 'Pedido recebido pelo sistema.',
					'state' => 'done',
				),
				array(
					'title' => wc_get_order_status_name( $status ),
					'desc'  => 'Pedido encerrado com o status atual: ' . wc_get_order_status_name( $status ) . '.',
					'state' => 'error',
				),
			);
		}

		$s1 = 'done';
		$s2 = in_array( $status, array( 'pending', 'on-hold' ), true ) ? 'current' : 'done';
		$s3 = 'processing' === $status && '' === $tracking
			? 'current'
			: ( 'completed' === $status || '' !== $tracking ? 'done' : 'pending' );
		$s4 = 'completed' === $status ? 'done' : ( '' !== $tracking ? 'current' : 'pending' );

		return array(
			array(
				'title' => 'Pedido Recebido',
				'desc'  => trim( ( $created_at ? $created_at . ' — ' : '' ) . 'Método: ' . $order->get_payment_method_title() ),
				'state' => $s1,
			),
			array(
				'title' => $paid_at ? 'Pagamento Confirmado' : 'Aguardando Confirmação de Pagamento',
				'desc'  => $paid_at ? 'Compensado em ' . $paid_at : 'Assim que compensado, seu pedido avança automaticamente.',
				'state' => $s2,
			),
			array(
				'title' => 'Preparação & Expedição',
				'desc'  => '' !== $shipping_ttl ? 'Método de entrega: ' . $shipping_ttl : 'Em conferência e separação para envio.',
				'state' => $s3,

			),
			array(
				'title' => 'Entrega Concluída',
				'desc'  => '' !== $tracking ? 'Código de rastreio: ' . $tracking : 'Código será exibido após a coleta.',
				'state' => $s4,
			),
		);
	}
}

add_shortcode(
	'store_order_tracking',
	function (): string {
		$req_id    = '';
		$req_email = '';

		if ( isset( $_POST['store_track_submit'], $_POST['store_track_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['store_track_nonce'] ) ), 'store_track_action' ) ) {
				$req_id    = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
				$req_email = isset( $_POST['order_email'] ) ? sanitize_email( wp_unslash( $_POST['order_email'] ) ) : '';
			}
		} elseif ( isset( $_GET['pedido'] ) ) {
			$req_id    = sanitize_text_field( wp_unslash( $_GET['pedido'] ) );
			$req_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
			if ( '' === $req_email && is_user_logged_in() ) {
				$user      = wp_get_current_user();
				$req_email = (string) $user->user_email;
			}
		}

		$order = null;
		$error = '';

		if ( '' !== $req_id && '' !== $req_email ) {
			$numeric_id = absint( ltrim( $req_id, '#' ) );
			$candidate  = $numeric_id > 0 ? wc_get_order( $numeric_id ) : false;

			if ( $candidate instanceof WC_Order && 0 === strcasecmp( trim( $candidate->get_billing_email() ), trim( $req_email ) ) ) {
				$order = $candidate;
			} else {
				$error = 'Não encontramos nenhum pedido correspondente a este número e e-mail de faturamento.';
			}
		} elseif ( '' !== $req_id && '' === $req_email && is_user_logged_in() ) {
			$numeric_id = absint( ltrim( $req_id, '#' ) );
			$candidate  = $numeric_id > 0 ? wc_get_order( $numeric_id ) : false;
			if ( $candidate instanceof WC_Order && ( (int) $candidate->get_customer_id() === get_current_user_id() || current_user_can( 'manage_woocommerce' ) ) ) {
				$order     = $candidate;
				$req_email = $candidate->get_billing_email();
			}
		}

		$wa_support = Store_Glossary::whatsapp_link(
			$order ? trim( store_term( 'whatsapp_order_prefix' ) . $order->get_order_number() ) : ''
		);

		ob_start();
		?>
		<div class="ds-tracking-wrap">
			<div class="ds-panel-card">
				<form method="post" action="" class="ds-tracking-form">
					<?php wp_nonce_field( 'store_track_action', 'store_track_nonce' ); ?>
					<input type="hidden" name="store_track_submit" value="1" />

					<div class="ds-form-grid ds-form-grid--2">
						<div class="ds-field">
							<label for="store-track-id">Número do Pedido *</label>
							<input id="store-track-id" type="text" name="order_id" required placeholder="Ex: 1234" value="<?php echo esc_attr( $req_id ); ?>" />
						</div>
						<div class="ds-field">
							<label for="store-track-email">E-mail de Faturamento *</label>
							<input id="store-track-email" type="email" name="order_email" required placeholder="seu@email.com" value="<?php echo esc_attr( $req_email ); ?>" />
						</div>
					</div>

					<button type="submit" class="ds-btn ds-btn-dark ds-btn-block ds-btn-lg">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
						Consultar Status do Pedido
					</button>
				</form>
			</div>

			<?php if ( '' !== $error ) : ?>
				<div class="ds-alert ds-alert--error"><?php echo esc_html( $error ); ?></div>
			<?php endif; ?>

			<?php if ( $order instanceof WC_Order ) : ?>
				<?php
				$tracking_code = store_get_order_tracking_code( $order );
				$steps         = store_build_order_timeline( $order );
				$view_url      = is_user_logged_in() && (int) $order->get_customer_id() === get_current_user_id()
					? $order->get_view_order_url()
					: $order->get_checkout_order_received_url();
				?>
				<div class="ds-panel-card ds-tracking-result">
					<div class="ds-tracking-result__head">
						<div>
							<span class="ds-eyebrow">Pedido #<?php echo esc_html( $order->get_order_number() ); ?></span>
							<h3 class="ds-tracking-result__status"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></h3>
						</div>
						<?php if ( '' !== $tracking_code ) : ?>
							<span class="ds-pill-badge ds-pill-badge--emerald">Código: <?php echo esc_html( $tracking_code ); ?></span>
						<?php endif; ?>
					</div>

					<div class="ds-timeline">
						<?php foreach ( $steps as $step ) : ?>
							<div class="ds-timeline__step ds-timeline__step--<?php echo esc_attr( $step['state'] ); ?>">
								<div class="ds-timeline__dot" aria-hidden="true"></div>
								<div>
									<strong class="ds-timeline__title"><?php echo esc_html( $step['title'] ); ?></strong>
									<p class="ds-timeline__desc"><?php echo esc_html( $step['desc'] ); ?></p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="ds-tracking-result__footer">
						<a href="<?php echo esc_url( $view_url ); ?>" class="ds-link-accent">Ver Comprovante Completo &rarr;</a>
						<?php if ( '' !== $wa_support ) : ?>
							<a href="<?php echo esc_url( $wa_support ); ?>" target="_blank" rel="noopener noreferrer" class="ds-btn ds-btn-whatsapp">
								Falar com Atendimento sobre o Envio
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
);
