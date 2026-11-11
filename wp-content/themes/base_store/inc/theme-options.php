<?php
/**
 * Painel de Opções e Glossário de Termos Canônicos da Loja.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe responsável pelo gerenciamento de termos do glossário e regras configuráveis da loja.
 * Não utiliza fallbacks hardcoded de nicho ou dados fictícios; os dados provêm de wp_options.
 */
class Store_Glossary {

	/**
	 * Nome da chave de opção no WordPress.
	 */
	const OPTION_KEY = 'store_glossary';

	/**
	 * Inicializa hooks do WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'update_option_' . self::OPTION_KEY, array( __CLASS__, 'flush_builder_cache' ) );
		add_shortcode( 'store_term', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'store_locations_rotator', array( __CLASS__, 'rotator_shortcode' ) );
	}

	/**
	 * Limpa o cache de renderização e CSS do Elementor quando o glossário é atualizado.
	 *
	 * @return void
	 */
	public static function flush_builder_cache() {
		delete_post_meta_by_key( '_elementor_element_cache' );
		if ( class_exists( '\\Elementor\\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Recupera todos os dados do glossário diretamente do banco de dados.
	 *
	 * @return array
	 */
	public static function all(): array {
		$data = get_option( self::OPTION_KEY, array() );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Recupera um termo específico do glossário.
	 * Retorna string vazia se o termo não existir no banco.
	 *
	 * @param string $key Chave do termo.
	 * @return string
	 */
	public static function get( string $key ): string {
		$glossary = self::all();

		if ( 'store_location' === $key ) {
			if ( ! empty( $glossary['store_location'] ) ) {
				return (string) $glossary['store_location'];
			}
			$locations = self::locations();
			return ! empty( $locations[0] ) ? (string) $locations[0] : '';
		}

		if ( 'store_cnpj' === $key ) {
			if ( ! empty( $glossary['store_cnpj'] ) ) {
				return (string) $glossary['store_cnpj'];
			}
			$cnpjs = self::cnpjs();
			return ! empty( $cnpjs[0] ) ? (string) $cnpjs[0] : '';
		}

		if ( 'whatsapp_number' === $key ) {
			if ( ! empty( $glossary['whatsapp_number'] ) ) {
				return (string) $glossary['whatsapp_number'];
			}
			$wa = self::whatsapp();
			return ! empty( $wa[0]['number'] ) ? (string) $wa[0]['number'] : '';
		}

		if ( 'whatsapp_url' === $key ) {
			if ( ! empty( $glossary['whatsapp_url'] ) ) {
				return (string) $glossary['whatsapp_url'];
			}
			$wa = self::whatsapp();
			return ! empty( $wa[0]['url'] ) ? (string) $wa[0]['url'] : '';
		}

		if ( 'support_email' === $key ) {
			$emails = self::support_emails();
			return ! empty( $emails[0]['email'] ) ? (string) $emails[0]['email'] : '';
		}

		if ( ! isset( $glossary[ $key ] ) ) {
			return '';
		}

		if ( is_array( $glossary[ $key ] ) ) {
			return '';
		}

		return (string) $glossary[ $key ];
	}

	/**
	 * Recupera um valor numérico inteiro do glossário.
	 *
	 * @param string $key Chave.
	 * @return int
	 */
	public static function get_int( string $key ): int {
		$val = self::get( $key );
		return is_numeric( $val ) ? (int) $val : 0;
	}

	/**
	 * Recupera um valor numérico decimal do glossário.
	 *
	 * @param string $key Chave.
	 * @return float
	 */
	public static function get_float( string $key ): float {
		$val = str_replace( ',', '.', self::get( $key ) );
		return is_numeric( $val ) ? (float) $val : 0.0;
	}

	/**
	 * Recupera uma lista separada por vírgula ou quebra de linha.
	 *
	 * @param string $key       Chave.
	 * @param string $separator Separador principal (',' ou "\n").
	 * @return array
	 */
	public static function get_list( string $key, string $separator = ',' ): array {
		$raw = self::get( $key );
		if ( '' === trim( $raw ) ) {
			return array();
		}

		if ( "\n" === $separator ) {
			$parts = preg_split( '/\r\n|\r|\n/', $raw );
		} else {
			$parts = explode( $separator, $raw );
		}

		$items = array();
		if ( is_array( $parts ) ) {
			foreach ( $parts as $item ) {
				$clean = trim( (string) $item );
				if ( '' !== $clean ) {
					$items[] = $clean;
				}
			}
		}

		return $items;
	}

	/**
	 * Decodifica um campo JSON armazenado no glossário.
	 *
	 * @param string $key Chave.
	 * @return array
	 */
	public static function get_json( string $key ): array {
		$raw = self::get( $key );
		if ( '' === trim( $raw ) ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Retorna a lista de localidades cadastradas no banco.
	 *
	 * @return array
	 */
	public static function locations(): array {
		$glossary = self::all();
		return ( ! empty( $glossary['store_locations'] ) && is_array( $glossary['store_locations'] ) )
			? $glossary['store_locations']
			: array();
	}

	/**
	 * Retorna a lista de CNPJs cadastrados no banco.
	 *
	 * @return array
	 */
	public static function cnpjs(): array {
		$glossary = self::all();
		return ( ! empty( $glossary['store_cnpjs'] ) && is_array( $glossary['store_cnpjs'] ) )
			? $glossary['store_cnpjs']
			: array();
	}

	/**
	 * Retorna a lista de contatos do WhatsApp cadastrados no banco.
	 *
	 * @return array
	 */
	public static function whatsapp(): array {
		$glossary = self::all();
		return ( ! empty( $glossary['whatsapp_contacts'] ) && is_array( $glossary['whatsapp_contacts'] ) )
			? $glossary['whatsapp_contacts']
			: array();
	}

	/**
	 * Retorna a lista de e-mails de atendimento cadastrados no banco.
	 *
	 * @return array
	 */
	public static function support_emails(): array {
		$glossary = self::all();
		return ( ! empty( $glossary['support_emails'] ) && is_array( $glossary['support_emails'] ) )
			? $glossary['support_emails']
			: array();
	}

	/**
	 * Retorna as redes sociais configuradas da loja.
	 *
	 * @return array
	 */
	public static function social(): array {
		$glossary = self::all();
		$social   = array();

		if ( ! empty( $glossary['store_instagram'] ) ) {
			$social['instagram'] = array(
				'label'  => 'Instagram',
				'url'    => $glossary['store_instagram'],
				'handle' => $glossary['store_instagram_handle'] ?? '',
			);
		}
		if ( ! empty( $glossary['store_facebook'] ) ) {
			$social['facebook'] = array(
				'label' => 'Facebook',
				'url'   => $glossary['store_facebook'],
			);
		}
		if ( ! empty( $glossary['store_youtube'] ) ) {
			$social['youtube'] = array(
				'label' => 'YouTube',
				'url'   => $glossary['store_youtube'],
			);
		}
		if ( ! empty( $glossary['store_tiktok'] ) ) {
			$social['tiktok'] = array(
				'label' => 'TikTok',
				'url'   => $glossary['store_tiktok'],
			);
		}

		return $social;
	}

	/**
	 * Retorna o link direto do WhatsApp com mensagem contextual anexada.
	 *
	 * @param string $message Mensagem adicional.
	 * @return string
	 */
	public static function whatsapp_link( string $message = '' ): string {
		$base = self::get( 'whatsapp_url' );
		if ( '' === $base ) {
			$digits = preg_replace( '/\D+/', '', self::get( 'whatsapp_number' ) );
			if ( '' !== $digits ) {
				if ( strlen( $digits ) <= 11 ) {
					$digits = '55' . $digits;
				}
				$base = 'https://wa.me/' . $digits;
			}
		}

		if ( '' === $base ) {
			return '';
		}

		if ( '' === trim( $message ) ) {
			return $base;
		}

		$parts = wp_parse_url( $base );
		$clean = ( ! empty( $parts['scheme'] ) ? $parts['scheme'] . '://' : 'https://' )
			. ( $parts['host'] ?? 'wa.me' )
			. ( $parts['path'] ?? '' );

		return add_query_arg( array( 'text' => $message ), $clean );
	}

	/**
	 * Shortcode [store_term key="..."] para consumo nos templates e Elementor.
	 * Retorna string vazia se a chave não existir.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string
	 */
	public static function shortcode( $atts ): string {
		$args = shortcode_atts(
			array(
				'key'    => '',
				'format' => 'text',
			),
			$atts,
			'store_term'
		);

		if ( empty( $args['key'] ) ) {
			return '';
		}

		$value = self::get( $args['key'] );
		if ( '' === $value ) {
			return '';
		}

		if ( 'url' === $args['format'] ) {
			return esc_url( $value );
		}

		if ( 'nl2br' === $args['format'] ) {
			return wp_kses_post( nl2br( esc_html( $value ) ) );
		}

		return esc_html( $value );
	}

	/**
	 * Shortcode [store_locations_rotator mode="topbar|footer" interval="3500"]
	 * Alterna dinamicamente entre as lojas físicas cadastradas na loja.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string
	 */
	public static function rotator_shortcode( $atts ): string {
		$args = shortcode_atts(
			array(
				'mode'     => 'topbar',
				'interval' => '3500',
			),
			$atts,
			'store_locations_rotator'
		);

		$locations = self::locations();
		if ( empty( $locations ) ) {
			$single = self::get( 'store_location' );
			if ( ! empty( $single ) ) {
				$locations = array( $single );
			}
		}

		if ( empty( $locations ) ) {
			return '';
		}

		$cnpjs = self::cnpjs();
		$mode  = sanitize_html_class( $args['mode'] );
		$time  = max( 2000, (int) $args['interval'] );

		static $rotator_script_done = false;
		$script = '';
		if ( ! $rotator_script_done ) {
			$rotator_script_done = true;
			$script = '<script>(function(){function runRotators(){var elms=document.querySelectorAll(".ds-locations-rotator:not([data-active-loop])");for(var i=0;i<elms.length;i++){(function(box){box.setAttribute("data-active-loop","1");var list=box.querySelectorAll(".ds-rotator-item");if(list.length<=1)return;var ms=parseInt(box.getAttribute("data-interval")||"3500",10);var c=0;setInterval(function(){list[c].classList.remove("is-active");c=(c+1)%list.length;list[c].classList.add("is-active");},ms);})(elms[i]);}}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",runRotators);}else{runRotators();}})();</script>';
		}

		$output = '<div class="ds-locations-rotator ds-locations-rotator--' . esc_attr( $mode ) . '" data-interval="' . esc_attr( (string) $time ) . '" aria-live="polite">';
		foreach ( $locations as $idx => $loc ) {
			$text = $loc;
			if ( 'footer' === $mode && ! empty( $cnpjs[ $idx ] ) ) {
				$text .= ' • CNPJ: ' . $cnpjs[ $idx ];
			}
			$active_cls = ( 0 === $idx ) ? ' is-active' : '';
			$output .= '<span class="ds-rotator-item' . $active_cls . '"><span class="ds-rotator-pin" aria-hidden="true">📍</span> <span class="ds-rotator-text">' . esc_html( $text ) . '</span></span>';
		}
		$output .= '</div>' . $script;

		return $output;
	}

	/**
	 * Registra o menu administrativo no painel do WordPress.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			'Glossário & Opções',
			'Glossário',
			'manage_options',
			'store-options',
			array( __CLASS__, 'render_page' ),
			'dashicons-cart',
			59
		);

		add_submenu_page(
			'store-options',
			'Glossário & Termos Canônicos',
			'Glossário & Termos',
			'manage_options',
			'store-options',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Registra o grupo de configurações na Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'store_glossary_group',
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitiza os dados submetidos pelo formulário sem injetar valores padrão.
	 *
	 * @param mixed $input Dados submetidos.
	 * @return array
	 */
	public static function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$sanitized = array();

		$simple_text_fields = array(
			'store_name',
			'store_legal_name',
			'store_tagline',
			'store_full_address',
			'store_hours',
			'store_about_title',
			'store_instagram',
			'store_instagram_handle',
			'store_facebook',
			'store_youtube',
			'store_tiktok',
			'topbar_shipping',
			'shipping_badge_label',
			'shipping_modal_name',
			'shipping_footer_desc',
			'reverse_logistics_term',
			'prep_badge_label',
			'prep_quality_title',
			'store_advantages_badge',
			'store_advantages_title',
			'warranty_title',
			'topbar_payment',
			'payment_gateway_label',
			'pix_discount_badge',
			'security_badge',
			'guarantee_seal',
			'cash_discount_label',
			'pix_gateway_ids',
			'installments_label',
			'pix_key',
			'pix_beneficiary',
			'pix_city',
			'search_placeholder',
			'coupon_input_placeholder',
			'popular_search_terms',
			'brand_attribute_slug',
			'card_spec_attributes',
			'catalog_filter_attributes',
			'size_guide_attributes',
			'highlight_badge_label',
			'whatsapp_pdp_prefix',
			'whatsapp_order_prefix',
		);

		foreach ( $simple_text_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
		}

		$textarea_fields = array(
			'store_about_desc',
			'prep_quality_desc',
			'prep_quality_bullets',
			'store_advantages_desc',
			'warranty_desc',
			'contact_subjects',
		);

		foreach ( $textarea_fields as $field ) {
			$sanitized[ $field ] = isset( $input[ $field ] ) ? sanitize_textarea_field( $input[ $field ] ) : '';
		}

		$int_fields = array(
			'max_installments',
			'hero_banner_desktop_id',
			'hero_banner_mobile_id',
		);

		foreach ( $int_fields as $field ) {
			$sanitized[ $field ] = ( isset( $input[ $field ] ) && '' !== $input[ $field ] )
				? (string) absint( $input[ $field ] )
				: '';
		}

		$float_fields = array(
			'cash_discount_percent',
			'min_installment_value',
			'free_shipping_threshold',
		);

		foreach ( $float_fields as $field ) {
			if ( isset( $input[ $field ] ) && '' !== trim( (string) $input[ $field ] ) ) {
				$num                 = floatval( str_replace( ',', '.', sanitize_text_field( $input[ $field ] ) ) );
				$sanitized[ $field ] = (string) max( 0, $num );
			} else {
				$sanitized[ $field ] = '';
			}
		}

		// JSON estruturado (ex: calculadora/guia de medidas)
		if ( isset( $input['size_guide_config'] ) && '' !== trim( (string) $input['size_guide_config'] ) ) {
			$raw_json = wp_unslash( $input['size_guide_config'] );
			$decoded  = json_decode( $raw_json, true );
			if ( is_array( $decoded ) ) {
				$sanitized['size_guide_config'] = wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			} else {
				$sanitized['size_guide_config'] = sanitize_textarea_field( $raw_json );
			}
		} else {
			$sanitized['size_guide_config'] = '';
		}

		// Repetidor: Localidades
		$sanitized['store_locations'] = array();
		if ( ! empty( $input['store_locations'] ) && is_array( $input['store_locations'] ) ) {
			foreach ( $input['store_locations'] as $loc ) {
				$clean = sanitize_text_field( $loc );
				if ( '' !== trim( $clean ) ) {
					$sanitized['store_locations'][] = $clean;
				}
			}
		}

		// Repetidor: CNPJs
		$sanitized['store_cnpjs'] = array();
		if ( ! empty( $input['store_cnpjs'] ) && is_array( $input['store_cnpjs'] ) ) {
			foreach ( $input['store_cnpjs'] as $cnpj ) {
				$clean = sanitize_text_field( $cnpj );
				if ( '' !== trim( $clean ) ) {
					$sanitized['store_cnpjs'][] = $clean;
				}
			}
		}

		// Repetidor: WhatsApp
		$sanitized['whatsapp_contacts'] = array();
		if ( ! empty( $input['whatsapp_contacts'] ) && is_array( $input['whatsapp_contacts'] ) ) {
			foreach ( $input['whatsapp_contacts'] as $contact ) {
				$label  = isset( $contact['label'] ) ? sanitize_text_field( $contact['label'] ) : '';
				$number = isset( $contact['number'] ) ? sanitize_text_field( $contact['number'] ) : '';
				$url    = isset( $contact['url'] ) ? esc_url_raw( $contact['url'] ) : '';

				if ( '' !== trim( $number ) || '' !== trim( $label ) ) {
					$sanitized['whatsapp_contacts'][] = array(
						'label'  => $label,
						'number' => $number,
						'url'    => $url,
					);
				}
			}
		}

		// Repetidor: E-mails
		$sanitized['support_emails'] = array();
		if ( ! empty( $input['support_emails'] ) && is_array( $input['support_emails'] ) ) {
			foreach ( $input['support_emails'] as $em ) {
				$label = isset( $em['label'] ) ? sanitize_text_field( $em['label'] ) : '';
				$email = isset( $em['email'] ) ? sanitize_email( $em['email'] ) : '';

				if ( '' !== trim( $email ) ) {
					$sanitized['support_emails'][] = array(
						'label' => $label,
						'email' => $email,
					);
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Renderiza a página de opções no admin do WordPress.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$glossary          = self::all();
		$val               = static function ( string $k ) use ( $glossary ): string {
			return isset( $glossary[ $k ] ) && ! is_array( $glossary[ $k ] ) ? (string) $glossary[ $k ] : '';
		};
		$store_locations   = ( ! empty( $glossary['store_locations'] ) && is_array( $glossary['store_locations'] ) ) ? $glossary['store_locations'] : array();
		$store_cnpjs       = ( ! empty( $glossary['store_cnpjs'] ) && is_array( $glossary['store_cnpjs'] ) ) ? $glossary['store_cnpjs'] : array();
		$whatsapp_contacts = ( ! empty( $glossary['whatsapp_contacts'] ) && is_array( $glossary['whatsapp_contacts'] ) ) ? $glossary['whatsapp_contacts'] : array();
		$support_emails    = ( ! empty( $glossary['support_emails'] ) && is_array( $glossary['support_emails'] ) ) ? $glossary['support_emails'] : array();
		$opt               = self::OPTION_KEY;
		?>
		<div class="wrap" style="max-width: 1080px; margin-top: 20px;">
			<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #FBBF24; padding-bottom: 12px;">
				<div>
					<h1 style="font-weight: 800; font-size: 26px; margin: 0; color: #18181B;">
						Glossário & Configurações da Loja
					</h1>
					<p style="margin: 4px 0 0; color: #64748B; font-size: 13px;">
						Todos os dados operacionais, fiscais e regras do catálogo sincronizados em <code>wp_options</code>. Use <code>[store_term key="..."]</code> ou <code>store_term('key')</code>.
					</p>
				</div>
				<div>
					<span style="background: #18181B; color: #FBBF24; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase;">
						Banco de Dados Ativo
					</span>
				</div>
			</div>

			<?php settings_errors(); ?>

			<form method="post" action="options.php" style="margin-top: 20px;">
				<?php settings_fields( 'store_glossary_group' ); ?>

				<!-- SEÇÃO A: IDENTIDADE & ATENDIMENTO -->
				<div class="postbox" style="border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden;">
					<div class="postbox-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 12px 20px;">
						<h2 style="font-weight: 700; font-size: 15px; color: #0F172A; margin: 0;">Seção A: Identidade, Sede & Atendimento</h2>
					</div>
					<div class="inside" style="padding: 20px;">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="store_name">Nome Comercial da Loja</label></th>
								<td>
									<input type="text" id="store_name" name="<?php echo esc_attr( $opt ); ?>[store_name]" value="<?php echo esc_attr( $val( 'store_name' ) ); ?>" class="regular-text">
									<p class="description">Chave: <code>store_name</code></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_legal_name">Razão Social (NF-e / Termos)</label></th>
								<td>
									<input type="text" id="store_legal_name" name="<?php echo esc_attr( $opt ); ?>[store_legal_name]" value="<?php echo esc_attr( $val( 'store_legal_name' ) ); ?>" class="large-text">
									<p class="description">Chave: <code>store_legal_name</code></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_tagline">Slogan / Posicionamento</label></th>
								<td>
									<input type="text" id="store_tagline" name="<?php echo esc_attr( $opt ); ?>[store_tagline]" value="<?php echo esc_attr( $val( 'store_tagline' ) ); ?>" class="large-text">
									<p class="description">Chave: <code>store_tagline</code></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_full_address">Endereço Físico Completo</label></th>
								<td>
									<input type="text" id="store_full_address" name="<?php echo esc_attr( $opt ); ?>[store_full_address]" value="<?php echo esc_attr( $val( 'store_full_address' ) ); ?>" class="large-text">
									<p class="description">Chave: <code>store_full_address</code></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_hours">Horário de Atendimento</label></th>
								<td>
									<input type="text" id="store_hours" name="<?php echo esc_attr( $opt ); ?>[store_hours]" value="<?php echo esc_attr( $val( 'store_hours' ) ); ?>" class="large-text">
									<p class="description">Chave: <code>store_hours</code></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_about_title">Sobre Nós — Título</label></th>
								<td>
									<input type="text" id="store_about_title" name="<?php echo esc_attr( $opt ); ?>[store_about_title]" value="<?php echo esc_attr( $val( 'store_about_title' ) ); ?>" class="large-text">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_about_desc">Sobre Nós — Texto</label></th>
								<td>
									<textarea id="store_about_desc" name="<?php echo esc_attr( $opt ); ?>[store_about_desc]" rows="4" class="large-text"><?php echo esc_textarea( $val( 'store_about_desc' ) ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row">Locais / Cidades / Unidades</th>
								<td>
									<div id="store-locations-container" style="display: flex; flex-direction: column; gap: 8px; max-width: 450px;">
										<?php foreach ( $store_locations as $loc ) : ?>
											<div class="store-repeater-row" style="display: flex; gap: 8px;">
												<input type="text" name="<?php echo esc_attr( $opt ); ?>[store_locations][]" value="<?php echo esc_attr( $loc ); ?>" style="flex: 1;">
												<button type="button" class="button store-remove-row" style="color: #DC2626;">&times;</button>
											</div>
										<?php endforeach; ?>
									</div>
									<button type="button" id="store-add-location" class="button button-secondary" style="margin-top: 8px;">+ Adicionar Local</button>
								</td>
							</tr>
							<tr>
								<th scope="row">CNPJs da Loja</th>
								<td>
									<div id="store-cnpjs-container" style="display: flex; flex-direction: column; gap: 8px; max-width: 450px;">
										<?php foreach ( $store_cnpjs as $cnpj ) : ?>
											<div class="store-repeater-row" style="display: flex; gap: 8px;">
												<input type="text" name="<?php echo esc_attr( $opt ); ?>[store_cnpjs][]" value="<?php echo esc_attr( $cnpj ); ?>" style="flex: 1;">
												<button type="button" class="button store-remove-row" style="color: #DC2626;">&times;</button>
											</div>
										<?php endforeach; ?>
									</div>
									<button type="button" id="store-add-cnpj" class="button button-secondary" style="margin-top: 8px;">+ Adicionar CNPJ</button>
								</td>
							</tr>
							<tr>
								<th scope="row">Contatos de WhatsApp</th>
								<td>
									<div id="store-whatsapp-container" style="display: flex; flex-direction: column; gap: 12px;">
										<?php foreach ( $whatsapp_contacts as $idx => $wa ) : ?>
											<div class="store-repeater-card" style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 8px; max-width: 650px; position: relative;">
												<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px;">
													<div>
														<label style="font-size: 11px; font-weight: 600; color: #475569;">Setor</label>
														<input type="text" name="<?php echo esc_attr( $opt ); ?>[whatsapp_contacts][<?php echo esc_attr( $idx ); ?>][label]" value="<?php echo esc_attr( $wa['label'] ?? '' ); ?>" style="width: 100%;">
													</div>
													<div>
														<label style="font-size: 11px; font-weight: 600; color: #475569;">Número com DDD</label>
														<input type="text" name="<?php echo esc_attr( $opt ); ?>[whatsapp_contacts][<?php echo esc_attr( $idx ); ?>][number]" value="<?php echo esc_attr( $wa['number'] ?? '' ); ?>" style="width: 100%;">
													</div>
												</div>
												<div>
													<label style="font-size: 11px; font-weight: 600; color: #475569;">Link Direto</label>
													<input type="text" name="<?php echo esc_attr( $opt ); ?>[whatsapp_contacts][<?php echo esc_attr( $idx ); ?>][url]" value="<?php echo esc_attr( $wa['url'] ?? '' ); ?>" style="width: 100%;">
												</div>
												<button type="button" class="button store-remove-row" style="position: absolute; top: 10px; right: 10px; color: #DC2626;">Remover</button>
											</div>
										<?php endforeach; ?>
									</div>
									<button type="button" id="store-add-whatsapp" class="button button-secondary" style="margin-top: 10px;">+ Adicionar WhatsApp</button>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="whatsapp_pdp_prefix">Prefixo WhatsApp (Produto / Pedido)</label></th>
								<td>
									<input type="text" id="whatsapp_pdp_prefix" name="<?php echo esc_attr( $opt ); ?>[whatsapp_pdp_prefix]" value="<?php echo esc_attr( $val( 'whatsapp_pdp_prefix' ) ); ?>" class="large-text" placeholder="Ex: Olá, gostaria de informações sobre o produto:">
									<input type="text" id="whatsapp_order_prefix" name="<?php echo esc_attr( $opt ); ?>[whatsapp_order_prefix]" value="<?php echo esc_attr( $val( 'whatsapp_order_prefix' ) ); ?>" class="large-text" style="margin-top: 6px;" placeholder="Ex: Olá, preciso de suporte para o pedido #">
								</td>
							</tr>
							<tr>
								<th scope="row">E-mails de Atendimento</th>
								<td>
									<div id="store-emails-container" style="display: flex; flex-direction: column; gap: 8px;">
										<?php foreach ( $support_emails as $idx => $em ) : ?>
											<div class="store-repeater-row" style="display: flex; gap: 8px; max-width: 550px;">
												<input type="text" name="<?php echo esc_attr( $opt ); ?>[support_emails][<?php echo esc_attr( $idx ); ?>][label]" value="<?php echo esc_attr( $em['label'] ?? '' ); ?>" style="width: 40%;">
												<input type="email" name="<?php echo esc_attr( $opt ); ?>[support_emails][<?php echo esc_attr( $idx ); ?>][email]" value="<?php echo esc_attr( $em['email'] ?? '' ); ?>" style="flex: 1;">
												<button type="button" class="button store-remove-row" style="color: #DC2626;">&times;</button>
											</div>
										<?php endforeach; ?>
									</div>
									<button type="button" id="store-add-email" class="button button-secondary" style="margin-top: 8px;">+ Adicionar E-mail</button>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="contact_subjects">Assuntos do Formulário de Contato (1 por linha)</label></th>
								<td>
									<textarea id="contact_subjects" name="<?php echo esc_attr( $opt ); ?>[contact_subjects]" rows="4" class="large-text"><?php echo esc_textarea( $val( 'contact_subjects' ) ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="store_instagram">Redes Sociais & Canais Digitais</label></th>
								<td>
									<div style="display: flex; flex-direction: column; gap: 10px; max-width: 650px;">
										<div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center;">
											<span style="font-size: 12px; font-weight: 600; color: #475569;">Instagram URL:</span>
											<input type="url" id="store_instagram" name="<?php echo esc_attr( $opt ); ?>[store_instagram]" value="<?php echo esc_attr( $val( 'store_instagram' ) ); ?>" class="large-text" placeholder="https://www.instagram.com/21.bikes/">
										</div>
										<div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center;">
											<span style="font-size: 12px; font-weight: 600; color: #475569;">Instagram @:</span>
											<input type="text" id="store_instagram_handle" name="<?php echo esc_attr( $opt ); ?>[store_instagram_handle]" value="<?php echo esc_attr( $val( 'store_instagram_handle' ) ); ?>" class="regular-text" placeholder="@21.bikes">
										</div>
										<div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center;">
											<span style="font-size: 12px; font-weight: 600; color: #475569;">Facebook URL:</span>
											<input type="url" id="store_facebook" name="<?php echo esc_attr( $opt ); ?>[store_facebook]" value="<?php echo esc_attr( $val( 'store_facebook' ) ); ?>" class="large-text" placeholder="https://facebook.com/...">
										</div>
										<div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center;">
											<span style="font-size: 12px; font-weight: 600; color: #475569;">YouTube URL:</span>
											<input type="url" id="store_youtube" name="<?php echo esc_attr( $opt ); ?>[store_youtube]" value="<?php echo esc_attr( $val( 'store_youtube' ) ); ?>" class="large-text" placeholder="https://youtube.com/c/...">
										</div>
										<div style="display: grid; grid-template-columns: 140px 1fr; gap: 10px; align-items: center;">
											<span style="font-size: 12px; font-weight: 600; color: #475569;">TikTok URL:</span>
											<input type="url" id="store_tiktok" name="<?php echo esc_attr( $opt ); ?>[store_tiktok]" value="<?php echo esc_attr( $val( 'store_tiktok' ) ); ?>" class="large-text" placeholder="https://tiktok.com/@...">
										</div>
									</div>
									<p class="description" style="margin-top: 6px;">Chaves: <code>store_instagram</code>, <code>store_instagram_handle</code>, <code>store_facebook</code>, <code>store_youtube</code>, <code>store_tiktok</code></p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- SEÇÃO B: LOGÍSTICA, PREPARAÇÃO & GARANTIA -->
				<div class="postbox" style="border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden;">
					<div class="postbox-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 12px 20px;">
						<h2 style="font-weight: 700; font-size: 15px; color: #0F172A; margin: 0;">Seção B: Logística, Preparação & Garantia</h2>
					</div>
					<div class="inside" style="padding: 20px;">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="topbar_shipping">Texto de Envio na Topbar</label></th>
								<td><input type="text" id="topbar_shipping" name="<?php echo esc_attr( $opt ); ?>[topbar_shipping]" value="<?php echo esc_attr( $val( 'topbar_shipping' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_badge_label">Rótulo do Selo de Envio</label></th>
								<td><input type="text" id="shipping_badge_label" name="<?php echo esc_attr( $opt ); ?>[shipping_badge_label]" value="<?php echo esc_attr( $val( 'shipping_badge_label' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_modal_name">Nome do Método de Frete</label></th>

								<td><input type="text" id="shipping_modal_name" name="<?php echo esc_attr( $opt ); ?>[shipping_modal_name]" value="<?php echo esc_attr( $val( 'shipping_modal_name' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="shipping_footer_desc">Descrição de Envio no Rodapé</label></th>
								<td><input type="text" id="shipping_footer_desc" name="<?php echo esc_attr( $opt ); ?>[shipping_footer_desc]" value="<?php echo esc_attr( $val( 'shipping_footer_desc' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="reverse_logistics_term">Termo de Logística Reversa / Troca</label></th>
								<td><input type="text" id="reverse_logistics_term" name="<?php echo esc_attr( $opt ); ?>[reverse_logistics_term]" value="<?php echo esc_attr( $val( 'reverse_logistics_term' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="free_shipping_threshold">Valor Mínimo Frete Grátis (R$)</label></th>
								<td><input type="text" id="free_shipping_threshold" name="<?php echo esc_attr( $opt ); ?>[free_shipping_threshold]" value="<?php echo esc_attr( $val( 'free_shipping_threshold' ) ); ?>" class="small-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="prep_badge_label">Selo de Qualidade / Revisão</label></th>
								<td><input type="text" id="prep_badge_label" name="<?php echo esc_attr( $opt ); ?>[prep_badge_label]" value="<?php echo esc_attr( $val( 'prep_badge_label' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="prep_quality_title">Preparação — Título</label></th>
								<td><input type="text" id="prep_quality_title" name="<?php echo esc_attr( $opt ); ?>[prep_quality_title]" value="<?php echo esc_attr( $val( 'prep_quality_title' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="prep_quality_desc">Preparação — Descrição</label></th>
								<td><textarea id="prep_quality_desc" name="<?php echo esc_attr( $opt ); ?>[prep_quality_desc]" rows="3" class="large-text"><?php echo esc_textarea( $val( 'prep_quality_desc' ) ); ?></textarea></td>
							</tr>
							<tr>
								<th scope="row"><label for="prep_quality_bullets">Preparação — Checklist (1 por linha)</label></th>
								<td><textarea id="prep_quality_bullets" name="<?php echo esc_attr( $opt ); ?>[prep_quality_bullets]" rows="4" class="large-text"><?php echo esc_textarea( $val( 'prep_quality_bullets' ) ); ?></textarea></td>
							</tr>
							<tr>
								<th scope="row"><label for="store_advantages_badge">Bloco Diferenciais (Selo / Título / Texto)</label></th>
								<td>
									<input type="text" id="store_advantages_badge" name="<?php echo esc_attr( $opt ); ?>[store_advantages_badge]" value="<?php echo esc_attr( $val( 'store_advantages_badge' ) ); ?>" class="regular-text" placeholder="Selo">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[store_advantages_title]" value="<?php echo esc_attr( $val( 'store_advantages_title' ) ); ?>" class="large-text" style="margin-top: 6px;" placeholder="Título">
									<textarea name="<?php echo esc_attr( $opt ); ?>[store_advantages_desc]" rows="3" class="large-text" style="margin-top: 6px;" placeholder="Descrição"><?php echo esc_textarea( $val( 'store_advantages_desc' ) ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="warranty_title">Garantia — Título e Descrição</label></th>
								<td>
									<input type="text" id="warranty_title" name="<?php echo esc_attr( $opt ); ?>[warranty_title]" value="<?php echo esc_attr( $val( 'warranty_title' ) ); ?>" class="large-text">
									<textarea name="<?php echo esc_attr( $opt ); ?>[warranty_desc]" rows="3" class="large-text" style="margin-top: 6px;"><?php echo esc_textarea( $val( 'warranty_desc' ) ); ?></textarea>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- SEÇÃO C: PAGAMENTO & CONDIÇÕES COMERCIAIS -->
				<div class="postbox" style="border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden;">
					<div class="postbox-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 12px 20px;">
						<h2 style="font-weight: 700; font-size: 15px; color: #0F172A; margin: 0;">Seção C: Pagamento, PIX & Parcelamento</h2>
					</div>
					<div class="inside" style="padding: 20px;">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="topbar_payment">Texto de Pagamento na Topbar</label></th>
								<td><input type="text" id="topbar_payment" name="<?php echo esc_attr( $opt ); ?>[topbar_payment]" value="<?php echo esc_attr( $val( 'topbar_payment' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="payment_gateway_label">Meio de Pagamento Oficial</label></th>
								<td><input type="text" id="payment_gateway_label" name="<?php echo esc_attr( $opt ); ?>[payment_gateway_label]" value="<?php echo esc_attr( $val( 'payment_gateway_label' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="pix_discount_badge">Rótulo do Desconto À Vista</label></th>
								<td><input type="text" id="pix_discount_badge" name="<?php echo esc_attr( $opt ); ?>[pix_discount_badge]" value="<?php echo esc_attr( $val( 'pix_discount_badge' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="cash_discount_percent">Desconto À Vista / PIX (%)</label></th>
								<td>
									<input type="text" id="cash_discount_percent" name="<?php echo esc_attr( $opt ); ?>[cash_discount_percent]" value="<?php echo esc_attr( $val( 'cash_discount_percent' ) ); ?>" class="small-text">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[cash_discount_label]" value="<?php echo esc_attr( $val( 'cash_discount_label' ) ); ?>" class="regular-text" placeholder="Ex: no PIX à vista">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pix_gateway_ids">IDs de Gateways para Desconto PIX</label></th>
								<td>
									<input type="text" id="pix_gateway_ids" name="<?php echo esc_attr( $opt ); ?>[pix_gateway_ids]" value="<?php echo esc_attr( $val( 'pix_gateway_ids' ) ); ?>" class="regular-text" placeholder="Ex: woo-mercado-pago-pix,bacs">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="pix_key">Chave / Beneficiário / Cidade PIX (BR Code)</label></th>
								<td>
									<input type="text" id="pix_key" name="<?php echo esc_attr( $opt ); ?>[pix_key]" value="<?php echo esc_attr( $val( 'pix_key' ) ); ?>" class="regular-text" placeholder="Chave PIX (EVP / CNPJ / E-mail)">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[pix_beneficiary]" value="<?php echo esc_attr( $val( 'pix_beneficiary' ) ); ?>" class="regular-text" placeholder="Recebedor (até 25 chars)">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[pix_city]" value="<?php echo esc_attr( $val( 'pix_city' ) ); ?>" class="small-text" placeholder="Cidade">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="max_installments">Parcelamento (Máx. Parcelas / Parcela Mínima / Rótulo)</label></th>
								<td>
									<input type="number" id="max_installments" name="<?php echo esc_attr( $opt ); ?>[max_installments]" value="<?php echo esc_attr( $val( 'max_installments' ) ); ?>" class="small-text" min="1" max="24">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[min_installment_value]" value="<?php echo esc_attr( $val( 'min_installment_value' ) ); ?>" class="small-text" placeholder="Min R$">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[installments_label]" value="<?php echo esc_attr( $val( 'installments_label' ) ); ?>" class="regular-text" placeholder="Ex: sem juros">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="security_badge">Selo de Segurança do Ambiente</label></th>
								<td><input type="text" id="security_badge" name="<?php echo esc_attr( $opt ); ?>[security_badge]" value="<?php echo esc_attr( $val( 'security_badge' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="guarantee_seal">Selo de Garantia de Compra</label></th>
								<td><input type="text" id="guarantee_seal" name="<?php echo esc_attr( $opt ); ?>[guarantee_seal]" value="<?php echo esc_attr( $val( 'guarantee_seal' ) ); ?>" class="regular-text"></td>
							</tr>
						</table>
					</div>
				</div>

				<!-- SEÇÃO D: CATÁLOGO, ATRIBUTOS & BUSCA -->
				<div class="postbox" style="border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden;">
					<div class="postbox-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 12px 20px;">
						<h2 style="font-weight: 700; font-size: 15px; color: #0F172A; margin: 0;">Seção D: Catálogo, Atributos & Busca (Agnóstico)</h2>
					</div>
					<div class="inside" style="padding: 20px;">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="hero_banner_desktop_id">Banner Hero Desktop / Mobile (Media IDs)</label></th>
								<td>
									<input type="number" id="hero_banner_desktop_id" name="<?php echo esc_attr( $opt ); ?>[hero_banner_desktop_id]" value="<?php echo esc_attr( $val( 'hero_banner_desktop_id' ) ); ?>" class="small-text" placeholder="Desktop ID">
									<input type="number" id="hero_banner_mobile_id" name="<?php echo esc_attr( $opt ); ?>[hero_banner_mobile_id]" value="<?php echo esc_attr( $val( 'hero_banner_mobile_id' ) ); ?>" class="small-text" placeholder="Mobile ID">
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="search_placeholder">Placeholder da Busca de Produtos</label></th>
								<td><input type="text" id="search_placeholder" name="<?php echo esc_attr( $opt ); ?>[search_placeholder]" value="<?php echo esc_attr( $val( 'search_placeholder' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="popular_search_terms">Termos Populares de Busca (vírgula)</label></th>
								<td><input type="text" id="popular_search_terms" name="<?php echo esc_attr( $opt ); ?>[popular_search_terms]" value="<?php echo esc_attr( $val( 'popular_search_terms' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="coupon_input_placeholder">Placeholder de Cupom</label></th>
								<td><input type="text" id="coupon_input_placeholder" name="<?php echo esc_attr( $opt ); ?>[coupon_input_placeholder]" value="<?php echo esc_attr( $val( 'coupon_input_placeholder' ) ); ?>" class="regular-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="brand_attribute_slug">Slug da Taxonomia de Marca</label></th>
								<td><input type="text" id="brand_attribute_slug" name="<?php echo esc_attr( $opt ); ?>[brand_attribute_slug]" value="<?php echo esc_attr( $val( 'brand_attribute_slug' ) ); ?>" class="regular-text" placeholder="Ex: pa_marca"></td>
							</tr>
							<tr>
								<th scope="row"><label for="card_spec_attributes">Atributos em Destaque nos Cards (vírgula, até 3 visíveis por item)</label></th>
								<td><input type="text" id="card_spec_attributes" name="<?php echo esc_attr( $opt ); ?>[card_spec_attributes]" value="<?php echo esc_attr( $val( 'card_spec_attributes' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="catalog_filter_attributes">Atributos Filtráveis na Barra Lateral (vírgula)</label></th>
								<td><input type="text" id="catalog_filter_attributes" name="<?php echo esc_attr( $opt ); ?>[catalog_filter_attributes]" value="<?php echo esc_attr( $val( 'catalog_filter_attributes' ) ); ?>" class="large-text"></td>
							</tr>
							<tr>
								<th scope="row"><label for="size_guide_attributes">Atributos Vinculados ao Guia de Medidas (vírgula)</label></th>
								<td><input type="text" id="size_guide_attributes" name="<?php echo esc_attr( $opt ); ?>[size_guide_attributes]" value="<?php echo esc_attr( $val( 'size_guide_attributes' ) ); ?>" class="large-text" placeholder="Ex: pa_tamanho"></td>
							</tr>
							<tr>
								<th scope="row"><label for="highlight_badge_label">Selo Padrão de Produtos em Destaque</label></th>
								<td><input type="text" id="highlight_badge_label" name="<?php echo esc_attr( $opt ); ?>[highlight_badge_label]" value="<?php echo esc_attr( $val( 'highlight_badge_label' ) ); ?>" class="regular-text"></td>
							</tr>
						</table>
					</div>
				</div>

				<!-- SEÇÃO E: GUIA DE MEDIDAS CONFIGURÁVEL -->
				<div class="postbox" style="border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 24px; overflow: hidden;">
					<div class="postbox-header" style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 12px 20px;">
						<h2 style="font-weight: 700; font-size: 15px; color: #0F172A; margin: 0;">Seção E: Tabela e Calculadora de Medidas (JSON)</h2>
					</div>
					<div class="inside" style="padding: 20px;">
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="size_guide_config">Configuração JSON do Guia</label></th>
								<td>
									<textarea id="size_guide_config" name="<?php echo esc_attr( $opt ); ?>[size_guide_config]" rows="12" class="large-text code" style="font-family: monospace; font-size: 12px;"><?php echo esc_textarea( $val( 'size_guide_config' ) ); ?></textarea>
									<p class="description">Define título, rótulos, limites do seletor, colunas e faixas recomendadas sem qualquer regra fixa em PHP.</p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<div style="margin-top: 20px;">
					<?php submit_button( 'Salvar Termos e Configurações', 'primary large', 'submit', false, array( 'style' => 'border-radius: 9999px; padding: 6px 28px; font-weight: 700;' ) ); ?>
				</div>
			</form>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			document.addEventListener('click', function(e) {
				if (e.target && e.target.classList.contains('store-remove-row')) {
					const row = e.target.closest('.store-repeater-row') || e.target.closest('.store-repeater-card');
					if (row) row.remove();
				}
			});

			const optKey = '<?php echo esc_js( self::OPTION_KEY ); ?>';
			const addLocBtn = document.getElementById('store-add-location');
			if (addLocBtn) {
				addLocBtn.addEventListener('click', function() {
					const container = document.getElementById('store-locations-container');
					const div = document.createElement('div');
					div.className = 'store-repeater-row';
					div.style.cssText = 'display: flex; gap: 8px;';
					div.innerHTML = `<input type="text" name="${optKey}[store_locations][]" value="" style="flex: 1;"><button type="button" class="button store-remove-row" style="color: #DC2626;">&times;</button>`;
					container.appendChild(div);
				});
			}

			const addCnpjBtn = document.getElementById('store-add-cnpj');
			if (addCnpjBtn) {
				addCnpjBtn.addEventListener('click', function() {
					const container = document.getElementById('store-cnpjs-container');
					const div = document.createElement('div');
					div.className = 'store-repeater-row';
					div.style.cssText = 'display: flex; gap: 8px;';
					div.innerHTML = `<input type="text" name="${optKey}[store_cnpjs][]" value="" style="flex: 1;"><button type="button" class="button store-remove-row" style="color: #DC2626;">&times;</button>`;
					container.appendChild(div);
				});
			}

			const addWaBtn = document.getElementById('store-add-whatsapp');
			if (addWaBtn) {
				addWaBtn.addEventListener('click', function() {
					const container = document.getElementById('store-whatsapp-container');
					const idx = Date.now();
					const card = document.createElement('div');
					card.className = 'store-repeater-card';
					card.style.cssText = 'background: #F8FAFC; border: 1px solid #E2E8F0; padding: 12px; border-radius: 8px; max-width: 650px; position: relative;';
					card.innerHTML = `<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px;"><input type="text" name="${optKey}[whatsapp_contacts][${idx}][label]" placeholder="Setor"><input type="text" name="${optKey}[whatsapp_contacts][${idx}][number]" placeholder="Número"></div><input type="text" name="${optKey}[whatsapp_contacts][${idx}][url]" placeholder="https://wa.me/..." style="width:100%;"><button type="button" class="button store-remove-row" style="position:absolute;top:10px;right:10px;color:#DC2626;">Remover</button>`;
					container.appendChild(card);
				});
			}

			const addEmailBtn = document.getElementById('store-add-email');
			if (addEmailBtn) {
				addEmailBtn.addEventListener('click', function() {
					const container = document.getElementById('store-emails-container');
					const idx = Date.now();
					const div = document.createElement('div');
					div.className = 'store-repeater-row';
					div.style.cssText = 'display: flex; gap: 8px; max-width: 550px;';
					div.innerHTML = `<input type="text" name="${optKey}[support_emails][${idx}][label]" placeholder="Setor" style="width:40%;"><input type="email" name="${optKey}[support_emails][${idx}][email]" placeholder="E-mail" style="flex:1;"><button type="button" class="button store-remove-row" style="color:#DC2626;">&times;</button>`;
					container.appendChild(div);
				});
			}
		});
		</script>
		<?php
	}
}

/**
 * Função auxiliar global desacoplada para obter termos do glossário.
 *
 * @param string $key Chave do termo.
 * @return string
 */
if ( ! function_exists( 'store_term' ) ) {
	function store_term( string $key ): string {
		return Store_Glossary::get( $key );
	}
}

Store_Glossary::init();
