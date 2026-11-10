<?php
/**
 * Campos fiscais e de entrega brasileiros no Checkout WooCommerce (PF/PJ, CPF, CNPJ, IE, Número, Bairro e Rastreio).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Sinaliza para plugins de logística (ex: Melhor Envio) que os campos fiscais brasileiros
// são integralmente gerenciados pelo tema, prevenindo injeções duplicadas de campos ou scripts concorrentes.
if ( ! defined( 'EXTRA_CHECKOUT_FIELDS_FOR_BRAZIL_FILE' ) ) {
	define( 'EXTRA_CHECKOUT_FIELDS_FOR_BRAZIL_FILE', true );
}
if ( ! defined( 'CSBMW_PLUGIN_FILE' ) ) {
	define( 'CSBMW_PLUGIN_FILE', __FILE__ );
}


if ( ! function_exists( 'store_is_valid_cpf' ) ) {

	/**
	 * Valida número de CPF usando cálculo oficial Mod-11.
	 *
	 * @param string $cpf CPF com ou sem pontuação.
	 * @return bool
	 */
	function store_is_valid_cpf( string $cpf ): bool {
		$digits = preg_replace( '/\D+/', '', $cpf );
		if ( 11 !== strlen( $digits ) || preg_match( '/^(\d)\1{10}$/', $digits ) ) {
			return false;
		}

		for ( $t = 9; $t < 11; $t++ ) {
			$sum = 0;
			for ( $i = 0; $i < $t; $i++ ) {
				$sum += (int) $digits[ $i ] * ( ( $t + 1 ) - $i );
			}
			$rem = ( ( 10 * $sum ) % 11 ) % 10;
			if ( (int) $digits[ $t ] !== $rem ) {
				return false;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'store_is_valid_cnpj' ) ) {
	/**
	 * Valida número de CNPJ usando cálculo oficial Mod-11.
	 *
	 * @param string $cnpj CNPJ com ou sem pontuação.
	 * @return bool
	 */
	function store_is_valid_cnpj( string $cnpj ): bool {
		$digits = preg_replace( '/\D+/', '', $cnpj );
		if ( 14 !== strlen( $digits ) || preg_match( '/^(\d)\1{13}$/', $digits ) ) {
			return false;
		}

		$weights_first  = array( 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 );
		$weights_second = array( 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 );

		$sum = 0;
		for ( $i = 0; $i < 12; $i++ ) {
			$sum += (int) $digits[ $i ] * $weights_first[ $i ];
		}
		$rem = $sum % 11;
		$d1  = $rem < 2 ? 0 : 11 - $rem;
		if ( (int) $digits[12] !== $d1 ) {
			return false;
		}

		$sum = 0;
		for ( $i = 0; $i < 13; $i++ ) {
			$sum += (int) $digits[ $i ] * $weights_second[ $i ];
		}
		$rem = $sum % 11;
		$d2  = $rem < 2 ? 0 : 11 - $rem;

		return (int) $digits[13] === $d2;
	}
}

add_filter(
	'woocommerce_checkout_fields',
	function ( array $fields ): array {
		$is_pj_post = isset( $_POST['billing_person_type'] ) && 'pj' === sanitize_key( wp_unslash( $_POST['billing_person_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( isset( $fields['billing'] ) ) {
			$b = &$fields['billing'];

			if ( isset( $b['billing_email'] ) ) {
				$b['billing_email']['priority']    = 10;
				$b['billing_email']['label']       = 'E-mail para confirmação e NF-e';
				$b['billing_email']['placeholder'] = 'seu.email@exemplo.com.br';
				$b['billing_email']['class']       = array( 'form-row-wide', 'ds-checkout-field' );
			}

			$b['billing_person_type'] = array(
				'type'     => 'radio',
				'label'    => 'Tipo de Cadastro',
				'required' => false,
				'default'  => $is_pj_post ? 'pj' : 'pf',
				'options'  => array(
					'pf' => 'Pessoa Física (CPF)',
					'pj' => 'Pessoa Jurídica (CNPJ)',
				),
				'priority' => 15,
				'class'    => array( 'form-row-wide', 'ds-person-type-row' ),
			);

			if ( isset( $b['billing_first_name'] ) ) {
				$b['billing_first_name']['priority'] = 20;
				$b['billing_first_name']['label']    = 'Nome';
				$b['billing_first_name']['required'] = ! $is_pj_post;
				$b['billing_first_name']['class']    = array( 'form-row-first', 'ds-pf-field', 'ds-checkout-field' );
			}

			if ( isset( $b['billing_last_name'] ) ) {
				$b['billing_last_name']['priority'] = 25;
				$b['billing_last_name']['label']    = 'Sobrenome';
				$b['billing_last_name']['required'] = ! $is_pj_post;
				$b['billing_last_name']['class']    = array( 'form-row-last', 'ds-pf-field', 'ds-checkout-field' );
			}

			$b['billing_cpf'] = array(
				'type'              => 'text',
				'label'             => 'CPF',
				'placeholder'       => '000.000.000-00',
				'required'          => false,
				'priority'          => 30,
				'class'             => array( 'form-row-first', 'ds-pf-field', 'ds-required-pf', 'ds-checkout-field' ),
				'custom_attributes' => array(
					'maxlength'      => '14',
					'data-mask'      => 'cpf',
					'autocomplete'   => 'off',
				),
			);

			$b['billing_birthdate'] = array(
				'type'     => 'date',
				'label'    => 'Data de Nascimento',
				'required' => false,
				'priority' => 35,
				'class'    => array( 'form-row-last', 'ds-pf-field', 'ds-checkout-field' ),
			);

			$b['billing_cnpj'] = array(
				'type'              => 'text',
				'label'             => 'CNPJ',
				'placeholder'       => '00.000.000/0000-00',
				'required'          => false,
				'priority'          => 40,
				'class'             => array( 'form-row-first', 'ds-pj-field', 'ds-required-pj', 'ds-checkout-field' ),
				'custom_attributes' => array(
					'maxlength' => '18',
					'data-mask' => 'cnpj',
				),
			);

			$b['billing_ie'] = array(
				'type'        => 'text',
				'label'       => 'Inscrição Estadual (IE)',
				'placeholder' => 'Inscrição Estadual ou marque Isento',
				'required'    => false,
				'priority'    => 45,
				'class'       => array( 'form-row-last', 'ds-pj-field', 'ds-required-pj', 'ds-checkout-field' ),
			);

			$b['billing_ie_exempt'] = array(
				'type'     => 'checkbox',
				'label'    => 'Isento de Inscrição Estadual (IE)',
				'required' => false,
				'priority' => 47,
				'class'    => array( 'form-row-wide', 'ds-pj-field', 'ds-ie-exempt-row' ),
			);

			if ( isset( $b['billing_company'] ) ) {
				$b['billing_company']['priority'] = 50;
				$b['billing_company']['label']    = 'Razão Social';
				$b['billing_company']['required'] = false;
				$b['billing_company']['class']    = array( 'form-row-first', 'ds-pj-field', 'ds-required-pj', 'ds-checkout-field' );
			}

			$b['billing_trade_name'] = array(
				'type'     => 'text',
				'label'    => 'Nome Fantasia',
				'required' => false,
				'priority' => 55,
				'class'    => array( 'form-row-last', 'ds-pj-field', 'ds-checkout-field' ),
			);

			$b['billing_responsible_name'] = array(
				'type'        => 'text',
				'label'       => 'Nome do Responsável pelo Recebimento',
				'placeholder' => 'Quem receberá o pedido na empresa',
				'required'    => false,
				'priority'    => 58,
				'class'       => array( 'form-row-wide', 'ds-pj-field', 'ds-required-pj', 'ds-checkout-field' ),
			);

			if ( isset( $b['billing_phone'] ) ) {
				$b['billing_phone']['priority']    = 60;
				$b['billing_phone']['label']       = 'WhatsApp / Celular com DDD';
				$b['billing_phone']['placeholder'] = '(00) 00000-0000';
				$b['billing_phone']['class']       = array( 'form-row-wide', 'ds-checkout-field' );
				$b['billing_phone']['custom_attributes'] = array(
					'data-mask' => 'phone',
					'maxlength' => '15',
				);
			}

			if ( isset( $b['billing_country'] ) ) {
				$b['billing_country']['priority'] = 65;
				$b['billing_country']['default']  = 'BR';
				$b['billing_country']['class']    = array( 'form-row-wide', 'ds-checkout-field', 'ds-country-field' );
			}

			if ( isset( $b['billing_postcode'] ) ) {
				$b['billing_postcode']['priority']    = 70;
				$b['billing_postcode']['label']       = 'CEP';
				$b['billing_postcode']['placeholder'] = '00000-000';
				$b['billing_postcode']['class']       = array( 'form-row-wide', 'ds-checkout-field', 'ds-cep-field' );
				$b['billing_postcode']['custom_attributes'] = array(
					'data-mask'     => 'cep',
					'data-viacep'   => 'billing',
					'maxlength'     => '9',
				);
			}

			if ( isset( $b['billing_address_1'] ) ) {
				$b['billing_address_1']['priority']    = 75;
				$b['billing_address_1']['label']       = 'Logradouro / Rua';
				$b['billing_address_1']['placeholder'] = 'Rua, Avenida...';
				$b['billing_address_1']['class']       = array( 'form-row-first', 'ds-checkout-field' );
			}

			$b['billing_number'] = array(
				'type'        => 'text',
				'label'       => 'Número',
				'placeholder' => 'Ex: 1420',
				'required'    => true,
				'priority'    => 78,
				'class'       => array( 'form-row-last', 'ds-checkout-field' ),
			);

			if ( isset( $b['billing_address_2'] ) ) {
				$b['billing_address_2']['priority']    = 80;
				$b['billing_address_2']['label']       = 'Complemento (Apto, Bloco)';
				$b['billing_address_2']['placeholder'] = 'Opcional';
				$b['billing_address_2']['required']    = false;
				$b['billing_address_2']['class']       = array( 'form-row-first', 'ds-checkout-field' );
			}

			$b['billing_neighborhood'] = array(
				'type'        => 'text',
				'label'       => 'Bairro',
				'placeholder' => 'Seu bairro',
				'required'    => true,
				'priority'    => 82,
				'class'       => array( 'form-row-last', 'ds-checkout-field' ),
			);

			if ( isset( $b['billing_city'] ) ) {
				$b['billing_city']['priority'] = 85;
				$b['billing_city']['label']    = 'Cidade';
				$b['billing_city']['class']    = array( 'form-row-first', 'ds-checkout-field' );
			}

			if ( isset( $b['billing_state'] ) ) {
				$b['billing_state']['priority'] = 90;
				$b['billing_state']['label']    = 'Estado (UF)';
				$b['billing_state']['class']    = array( 'form-row-last', 'ds-checkout-field' );
			}
		}

		if ( isset( $fields['shipping'] ) ) {
			$s = &$fields['shipping'];

			if ( isset( $s['shipping_country'] ) ) {
				$s['shipping_country']['priority'] = 10;
				$s['shipping_country']['default']  = 'BR';
			}

			if ( isset( $s['shipping_postcode'] ) ) {
				$s['shipping_postcode']['priority']    = 30;
				$s['shipping_postcode']['label']       = 'CEP de Entrega';
				$s['shipping_postcode']['placeholder'] = '00000-000';
				$s['shipping_postcode']['class']       = array( 'form-row-wide', 'ds-checkout-field', 'ds-cep-field' );
				$s['shipping_postcode']['custom_attributes'] = array(
					'data-mask'   => 'cep',
					'data-viacep' => 'shipping',
					'maxlength'   => '9',
				);
			}

			if ( isset( $s['shipping_address_1'] ) ) {
				$s['shipping_address_1']['priority'] = 40;
				$s['shipping_address_1']['label']    = 'Logradouro / Rua';
				$s['shipping_address_1']['class']    = array( 'form-row-first', 'ds-checkout-field' );
			}

			$s['shipping_number'] = array(
				'type'        => 'text',
				'label'       => 'Número',
				'placeholder' => 'Ex: 1420',
				'required'    => false,
				'priority'    => 45,
				'class'       => array( 'form-row-last', 'ds-checkout-field' ),
			);

			if ( isset( $s['shipping_address_2'] ) ) {
				$s['shipping_address_2']['priority'] = 50;
				$s['shipping_address_2']['label']    = 'Complemento';
				$s['shipping_address_2']['class']    = array( 'form-row-first', 'ds-checkout-field' );
			}

			$s['shipping_neighborhood'] = array(
				'type'        => 'text',
				'label'       => 'Bairro',
				'placeholder' => 'Bairro de entrega',
				'required'    => false,
				'priority'    => 55,
				'class'       => array( 'form-row-last', 'ds-checkout-field' ),
			);
		}

		return $fields;
	},
	20
);

add_filter(
	'woocommerce_form_field',
	function ( string $field, string $key, array $args, $value ): string {
		// Marca asterisco visual nos campos obrigatórios condicionalmente (PF/PJ).
		if ( ! empty( $args['class'] ) && ( in_array( 'ds-required-pf', $args['class'], true ) || in_array( 'ds-required-pj', $args['class'], true ) ) ) {
			$field = str_replace( '&nbsp;<span class="optional">(opcional)</span>', ' <abbr class="required" title="obrigatório">*</abbr>', $field );
			$field = str_replace( '<span class="optional">(optional)</span>', '<abbr class="required" title="obrigatório">*</abbr>', $field );
		}

		// Adiciona botão de busca ViaCEP inline ao lado do CEP de cobrança/entrega.
		if ( in_array( $key, array( 'billing_postcode', 'shipping_postcode' ), true ) ) {
			$target = 'billing_postcode' === $key ? 'billing' : 'shipping';
			$btn    = '<button type="button" class="ds-btn ds-btn-soft ds-viacep-btn" data-viacep-trigger="' . esc_attr( $target ) . '">Buscar CEP</button>';
			$field  = preg_replace(
				'/(<span class="woocommerce-input-wrapper">)(.*?)(<\/span>)/s',
				'$1<div class="ds-cep-inline-wrap">$2' . $btn . '</div><span class="ds-viacep-status" data-viacep-status="' . esc_attr( $target ) . '"></span>$3',
				$field,
				1
			);
		}

		return $field;
	},
	20,
	4
);

add_filter(
	'woocommerce_checkout_posted_data',
	function ( array $data ): array {
		if ( empty( $data['billing_country'] ) ) {
			$data['billing_country'] = 'BR';
		}

		$person_type = isset( $data['billing_person_type'] ) ? sanitize_key( (string) $data['billing_person_type'] ) : 'pf';
		if ( 'pj' === $person_type ) {
			$resp  = trim( (string) ( $data['billing_responsible_name'] ?? '' ) );
			$comp  = trim( (string) ( $data['billing_company'] ?? '' ) );
			$base  = '' !== $resp ? $resp : $comp;
			$parts = preg_split( '/\s+/', $base, 2 );

			if ( empty( $data['billing_first_name'] ) && ! empty( $parts[0] ) ) {
				$data['billing_first_name'] = $parts[0];
			}
			if ( empty( $data['billing_last_name'] ) ) {
				$data['billing_last_name'] = ! empty( $parts[1] ) ? $parts[1] : 'Empresa';
			}
		}

		return $data;
	}
);

add_action(
	'woocommerce_checkout_process',
	function () {
		$person_type = isset( $_POST['billing_person_type'] ) ? sanitize_key( wp_unslash( $_POST['billing_person_type'] ) ) : 'pf'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( 'pj' === $person_type ) {
			$cnpj       = isset( $_POST['billing_cnpj'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_cnpj'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$company    = isset( $_POST['billing_company'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_company'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$ie         = isset( $_POST['billing_ie'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_ie'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$ie_exempt  = ! empty( $_POST['billing_ie_exempt'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$resp_name  = isset( $_POST['billing_responsible_name'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_responsible_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( '' === trim( $cnpj ) || ! store_is_valid_cnpj( $cnpj ) ) {
				wc_add_notice( 'Informe um <strong>CNPJ válido</strong> para emissão da Nota Fiscal.', 'error' );
			}
			if ( '' === trim( $company ) ) {
				wc_add_notice( 'Informe a <strong>Razão Social</strong> da empresa.', 'error' );
			}
			if ( ! $ie_exempt && '' === trim( $ie ) ) {
				wc_add_notice( 'Informe a <strong>Inscrição Estadual (IE)</strong> ou marque a opção de Isento.', 'error' );
			}
			if ( '' === trim( $resp_name ) ) {
				wc_add_notice( 'Informe o <strong>Nome do Responsável pelo Recebimento</strong>.', 'error' );
			}
		} else {
			$cpf = isset( $_POST['billing_cpf'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_cpf'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( '' === trim( $cpf ) || ! store_is_valid_cpf( $cpf ) ) {
				wc_add_notice( 'Informe um <strong>CPF válido</strong> para emissão da Nota Fiscal.', 'error' );
			}
		}

		$bill_num  = isset( $_POST['billing_number'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_number'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$bill_nei  = isset( $_POST['billing_neighborhood'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_neighborhood'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' === trim( $bill_num ) ) {
			wc_add_notice( 'Informe o <strong>Número</strong> do endereço de cobrança/entrega.', 'error' );
		}
		if ( '' === trim( $bill_nei ) ) {
			wc_add_notice( 'Informe o <strong>Bairro</strong> do endereço de cobrança/entrega.', 'error' );
		}
	}
);

add_action(
	'woocommerce_checkout_create_order',
	function ( WC_Order $order, array $data ) {
		$keys = array(
			'billing_person_type',
			'billing_cpf',
			'billing_birthdate',
			'billing_cnpj',
			'billing_ie',
			'billing_ie_exempt',
			'billing_trade_name',
			'billing_responsible_name',
			'billing_number',
			'billing_neighborhood',
			'shipping_number',
			'shipping_neighborhood',
		);

		foreach ( $keys as $k ) {
			if ( 'billing_ie_exempt' === $k ) {
				$val = ! empty( $data[ $k ] ) ? 'yes' : 'no';
				$order->update_meta_data( '_' . $k, $val );
			} elseif ( isset( $data[ $k ] ) ) {
				$order->update_meta_data( '_' . $k, sanitize_text_field( (string) $data[ $k ] ) );
			}
		}

		// Compatibilidade com plugins que consomem _billing_persontype ('1' = PF, '2' = PJ).
		$order->update_meta_data( '_billing_persontype', ( isset( $data['billing_person_type'] ) && 'pj' === $data['billing_person_type'] ) ? '2' : '1' );


		// Espelha dados de entrega se o cliente não marcou "Entregar em endereço diferente".
		if ( empty( $data['ship_to_different_address'] ) ) {
			if ( ! empty( $data['billing_number'] ) ) {
				$order->update_meta_data( '_shipping_number', sanitize_text_field( (string) $data['billing_number'] ) );
			}
			if ( ! empty( $data['billing_neighborhood'] ) ) {
				$order->update_meta_data( '_shipping_neighborhood', sanitize_text_field( (string) $data['billing_neighborhood'] ) );
			}
		}
	},
	10,
	2
);

add_filter(
	'woocommerce_order_formatted_billing_address',
	function ( array $address, WC_Order $order ): array {
		$num = trim( (string) $order->get_meta( '_billing_number' ) );
		$nei = trim( (string) $order->get_meta( '_billing_neighborhood' ) );
		if ( '' !== $num && ! empty( $address['address_1'] ) ) {
			$address['address_1'] .= ', ' . $num;
		}
		if ( '' !== $nei ) {
			$address['address_2'] = trim( ( ! empty( $address['address_2'] ) ? $address['address_2'] . ' • ' : '' ) . $nei );
		}
		return $address;
	},
	10,
	2
);

add_filter(
	'woocommerce_order_formatted_shipping_address',
	function ( array $address, WC_Order $order ): array {
		$num = trim( (string) $order->get_meta( '_shipping_number' ) );
		$nei = trim( (string) $order->get_meta( '_shipping_neighborhood' ) );
		if ( '' !== $num && ! empty( $address['address_1'] ) ) {
			$address['address_1'] .= ', ' . $num;
		}
		if ( '' !== $nei ) {
			$address['address_2'] = trim( ( ! empty( $address['address_2'] ) ? $address['address_2'] . ' • ' : '' ) . $nei );
		}
		return $address;
	},
	10,
	2
);

add_action(
	'woocommerce_admin_order_data_after_billing_address',
	function ( WC_Order $order ) {
		$ptype = $order->get_meta( '_billing_person_type' ) ?: 'pf';
		echo '<div class="order_data_column" style="margin-top:12px;">';
		echo '<p><strong>Dados Fiscais (Brasil)</strong></p>';
		if ( 'pj' === $ptype ) {
			$ie_text = 'yes' === $order->get_meta( '_billing_ie_exempt' ) ? 'Isento' : $order->get_meta( '_billing_ie' );
			echo '<p><strong>Tipo:</strong> Pessoa Jurídica (PJ)</p>';
			echo '<p><strong>CNPJ:</strong> ' . esc_html( (string) $order->get_meta( '_billing_cnpj' ) ) . '</p>';
			echo '<p><strong>IE:</strong> ' . esc_html( (string) $ie_text ) . '</p>';
			if ( $order->get_meta( '_billing_trade_name' ) ) {
				echo '<p><strong>Nome Fantasia:</strong> ' . esc_html( (string) $order->get_meta( '_billing_trade_name' ) ) . '</p>';
			}
			if ( $order->get_meta( '_billing_responsible_name' ) ) {
				echo '<p><strong>Responsável:</strong> ' . esc_html( (string) $order->get_meta( '_billing_responsible_name' ) ) . '</p>';
			}
		} else {
			echo '<p><strong>Tipo:</strong> Pessoa Física (PF)</p>';
			echo '<p><strong>CPF:</strong> ' . esc_html( (string) $order->get_meta( '_billing_cpf' ) ) . '</p>';
			if ( $order->get_meta( '_billing_birthdate' ) ) {
				echo '<p><strong>Nascimento:</strong> ' . esc_html( (string) $order->get_meta( '_billing_birthdate' ) ) . '</p>';
			}
		}
		if ( $order->get_meta( '_billing_number' ) || $order->get_meta( '_billing_neighborhood' ) ) {
			echo '<p><strong>Número / Bairro:</strong> ' . esc_html( trim( $order->get_meta( '_billing_number' ) . ' — ' . $order->get_meta( '_billing_neighborhood' ) ) ) . '</p>';
		}
		echo '</div>';
	}
);

add_action(
	'woocommerce_admin_order_data_after_shipping_address',
	function ( WC_Order $order ) {
		$code = (string) $order->get_meta( '_tracking_code' );
		?>
		<p class="form-field form-field-wide">
			<label for="store_tracking_code"><strong>Código de Rastreio:</strong></label>
			<input type="text" id="store_tracking_code" name="store_tracking_code" value="<?php echo esc_attr( $code ); ?>" placeholder="Ex: BR123456789" style="width:100%;" />
		</p>
		<?php
	}
);

add_action(
	'woocommerce_process_shop_order_meta',
	function ( int $order_id ) {
		if ( isset( $_POST['store_tracking_code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order = wc_get_order( $order_id );
			if ( $order instanceof WC_Order ) {
				$order->update_meta_data( '_tracking_code', sanitize_text_field( wp_unslash( $_POST['store_tracking_code'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$order->save();
			}
		}
	},
	30
);
