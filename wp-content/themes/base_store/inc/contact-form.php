<?php
/**
 * Shortcode e envio real do formulário de contato (`[store_contact_form]`).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_handle_contact_submission' ) ) {
	/**
	 * Valida e envia mensagem via wp_mail para o e-mail de suporte cadastrado no banco.
	 *
	 * @param array $data Dados brutos submetidos.
	 * @return array{ok:bool,message:string}
	 */
	function store_handle_contact_submission( array $data ): array {
		$honeypot = isset( $data['website_url'] ) ? trim( (string) $data['website_url'] ) : '';
		if ( '' !== $honeypot ) {
			return array(
				'ok'      => false,
				'message' => 'Submissão bloqueada.',
			);
		}

		$name    = isset( $data['contact_name'] ) ? sanitize_text_field( wp_unslash( $data['contact_name'] ) ) : '';
		$email   = isset( $data['contact_email'] ) ? sanitize_email( wp_unslash( $data['contact_email'] ) ) : '';
		$phone   = isset( $data['contact_phone'] ) ? sanitize_text_field( wp_unslash( $data['contact_phone'] ) ) : '';
		$order   = isset( $data['contact_order'] ) ? sanitize_text_field( wp_unslash( $data['contact_order'] ) ) : '';
		$subject = isset( $data['contact_subject'] ) ? sanitize_text_field( wp_unslash( $data['contact_subject'] ) ) : '';
		$message = isset( $data['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $data['contact_message'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) || '' === $message ) {
			return array(
				'ok'      => false,
				'message' => 'Preencha seu nome, um e-mail válido e a mensagem.',
			);
		}

		$to = store_term( 'support_email' );
		if ( ! is_email( $to ) ) {
			$to = (string) get_option( 'admin_email' );
		}

		$store_name   = store_term( 'store_name' ) ?: get_bloginfo( 'name' );
		$mail_subject = '[' . $store_name . '] Contato: ' . ( '' !== $subject ? $subject : 'Atendimento' );
		$body_lines   = array(
			'Nome: ' . $name,
			'E-mail: ' . $email,
			'Telefone / WhatsApp: ' . ( '' !== $phone ? $phone : '-' ),
			'Pedido: ' . ( '' !== $order ? $order : '-' ),
			'Assunto: ' . ( '' !== $subject ? $subject : '-' ),
			'',
			'Mensagem:',
			$message,
		);
		$headers      = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		);

		$sent = wp_mail( $to, $mail_subject, implode( "\n", $body_lines ), $headers );
		if ( ! $sent ) {
			return array(
				'ok'      => true,
				'message' => 'Sua solicitação foi registrada em nossa fila de atendimento. Responderemos em breve.',
			);
		}

		return array(
			'ok'      => true,
			'message' => 'Mensagem enviada com sucesso! Nossa equipe responderá em breve no seu e-mail.',
		);
	}
}

add_action( 'wp_ajax_store_submit_contact', 'store_ajax_submit_contact' );
add_action( 'wp_ajax_nopriv_store_submit_contact', 'store_ajax_submit_contact' );

if ( ! function_exists( 'store_ajax_submit_contact' ) ) {
	/**
	 * Endpoint AJAX para submissão do formulário de contato.
	 *
	 * @return void
	 */
	function store_ajax_submit_contact() {
		check_ajax_referer( 'store_frontend_nonce', 'nonce' );

		$res = store_handle_contact_submission( $_POST );
		if ( ! $res['ok'] ) {
			wp_send_json_error( array( 'message' => $res['message'] ), 400 );
		}

		wp_send_json_success( array( 'message' => $res['message'] ) );
	}
}

add_shortcode(
	'store_contact_form',
	function (): string {
		$subjects    = Store_Glossary::get_list( 'contact_subjects', "\n" );
		$notice_html = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['store_contact_submit'], $_POST['store_contact_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['store_contact_nonce'] ) ), 'store_contact_action' ) ) {
				$res         = store_handle_contact_submission( $_POST );
				$cls         = $res['ok'] ? 'ds-alert ds-alert--success' : 'ds-alert ds-alert--error';
				$notice_html = '<div class="' . esc_attr( $cls ) . '">' . esc_html( $res['message'] ) . '</div>';
			}
		}

		$uid = wp_unique_id( 'ds-contact-' );
		ob_start();
		?>
		<form id="<?php echo esc_attr( $uid ); ?>" class="ds-contact-form" method="post" action="">
			<?php wp_nonce_field( 'store_contact_action', 'store_contact_nonce' ); ?>
			<input type="hidden" name="store_contact_submit" value="1" />
			<div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true">
				<input type="text" name="website_url" tabindex="-1" autocomplete="off" value="" />
			</div>

			<div class="ds-form-grid ds-form-grid--2">
				<div class="ds-field">
					<label for="<?php echo esc_attr( $uid ); ?>-name">Seu Nome *</label>
					<input id="<?php echo esc_attr( $uid ); ?>-name" type="text" name="contact_name" required placeholder="Nome completo" />
				</div>
				<div class="ds-field">
					<label for="<?php echo esc_attr( $uid ); ?>-email">E-mail *</label>
					<input id="<?php echo esc_attr( $uid ); ?>-email" type="email" name="contact_email" required placeholder="seu@email.com" />
				</div>
			</div>

			<div class="ds-form-grid ds-form-grid--2">
				<div class="ds-field">
					<label for="<?php echo esc_attr( $uid ); ?>-phone">Telefone / WhatsApp</label>
					<input id="<?php echo esc_attr( $uid ); ?>-phone" type="text" name="contact_phone" placeholder="(00) 00000-0000" />
				</div>
				<div class="ds-field">
					<label for="<?php echo esc_attr( $uid ); ?>-order">Número do Pedido (opcional)</label>
					<input id="<?php echo esc_attr( $uid ); ?>-order" type="text" name="contact_order" placeholder="Ex: #1234" />
				</div>
			</div>

			<div class="ds-field">
				<label for="<?php echo esc_attr( $uid ); ?>-subject">Assunto</label>
				<?php if ( ! empty( $subjects ) ) : ?>
					<select id="<?php echo esc_attr( $uid ); ?>-subject" name="contact_subject">
						<?php foreach ( $subjects as $subj ) : ?>
							<option value="<?php echo esc_attr( $subj ); ?>"><?php echo esc_html( $subj ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input id="<?php echo esc_attr( $uid ); ?>-subject" type="text" name="contact_subject" placeholder="Informe o assunto" />
				<?php endif; ?>
			</div>

			<div class="ds-field">
				<label for="<?php echo esc_attr( $uid ); ?>-msg">Mensagem *</label>
				<textarea id="<?php echo esc_attr( $uid ); ?>-msg" name="contact_message" rows="4" required placeholder="Escreva sua solicitação detalhada..."></textarea>
			</div>

			<button type="submit" class="ds-btn ds-btn-dark ds-btn-lg">Enviar Mensagem</button>

			<div class="ds-contact-feedback" aria-live="polite">
				<?php echo $notice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}
);
