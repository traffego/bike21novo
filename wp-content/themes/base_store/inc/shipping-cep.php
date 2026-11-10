<?php
/**
 * Consulta ViaCEP e cotação real de frete WooCommerce (por produto ou carrinho).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_lookup_viacep' ) ) {
	/**
	 * Consulta dados de endereço pelo CEP na API ViaCEP com cache transitório.
	 *
	 * @param string $cep CEP com ou sem hífen.
	 * @return array{ok:bool,cep:string,logradouro:string,bairro:string,cidade:string,uf:string}
	 */
	function store_lookup_viacep( string $cep ): array {
		$digits = preg_replace( '/\D+/', '', $cep );
		if ( 8 !== strlen( $digits ) ) {
			return array(
				'ok'         => false,
				'cep'        => $digits,
				'logradouro' => '',
				'bairro'     => '',
				'cidade'     => '',
				'uf'         => '',
			);
		}

		$cache_key = 'store_cep_' . $digits;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = array(
			'ok'         => false,
			'cep'        => substr( $digits, 0, 5 ) . '-' . substr( $digits, 5, 3 ),
			'logradouro' => '',
			'bairro'     => '',
			'cidade'     => '',
			'uf'         => '',
		);

		$response = wp_remote_get(
			'https://viacep.com.br/ws/' . $digits . '/json/',
			array(
				'timeout' => 3,
			)
		);

		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( is_array( $body ) && empty( $body['erro'] ) ) {
				$result['ok']         = true;
				$result['logradouro'] = sanitize_text_field( (string) ( $body['logradouro'] ?? '' ) );
				$result['bairro']     = sanitize_text_field( (string) ( $body['bairro'] ?? '' ) );
				$result['cidade']     = sanitize_text_field( (string) ( $body['localidade'] ?? '' ) );
				$result['uf']         = sanitize_text_field( (string) ( $body['uf'] ?? '' ) );
				set_transient( $cache_key, $result, DAY_IN_SECONDS );
			}
		}

		return $result;
	}
}

add_action( 'wp_ajax_store_viacep_lookup', 'store_ajax_viacep_lookup' );
add_action( 'wp_ajax_nopriv_store_viacep_lookup', 'store_ajax_viacep_lookup' );

if ( ! function_exists( 'store_ajax_viacep_lookup' ) ) {
	/**
	 * Endpoint AJAX para busca de endereço por CEP.
	 *
	 * @return void
	 */
	function store_ajax_viacep_lookup() {
		check_ajax_referer( 'store_frontend_nonce', 'nonce' );

		$cep  = isset( $_POST['cep'] ) ? sanitize_text_field( wp_unslash( $_POST['cep'] ) ) : '';
		$info = store_lookup_viacep( $cep );
		if ( ! $info['ok'] ) {
			wp_send_json_error( array( 'message' => 'CEP não localizado ou inválido.' ), 404 );
		}

		wp_send_json_success( $info );
	}
}

add_action( 'wp_ajax_store_calculate_shipping', 'store_ajax_calculate_shipping' );
add_action( 'wp_ajax_nopriv_store_calculate_shipping', 'store_ajax_calculate_shipping' );

if ( ! function_exists( 'store_ajax_calculate_shipping' ) ) {
	/**
	 * Calcula as opções reais de envio configuradas nas Zonas do WooCommerce para o CEP informado.
	 *
	 * @return void
	 */
	function store_ajax_calculate_shipping() {
		check_ajax_referer( 'store_frontend_nonce', 'nonce' );

		$cep        = isset( $_POST['cep'] ) ? sanitize_text_field( wp_unslash( $_POST['cep'] ) ) : '';
		$digits     = preg_replace( '/\D+/', '', $cep );
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$qty        = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;

		if ( 8 !== strlen( $digits ) ) {
			wp_send_json_error( array( 'message' => 'Informe um CEP válido com 8 dígitos.' ), 400 );
		}

		$formatted_cep = substr( $digits, 0, 5 ) . '-' . substr( $digits, 5, 3 );
		$address       = store_lookup_viacep( $formatted_cep );
		$state         = $address['uf'];
		$city          = $address['cidade'];

		if ( function_exists( 'wc_load_cart' ) && null === WC()->cart ) {
			wc_load_cart();
		}

		$destination = array(
			'country'   => 'BR',
			'state'     => $state,
			'postcode'  => $formatted_cep,
			'city'      => $city,
			'address'   => $address['logradouro'],
			'address_1' => $address['logradouro'],
			'address_2' => '',
		);

		$rates_out = array();

		if ( $product_id > 0 ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( array( 'message' => 'Produto não encontrado.' ), 404 );
			}

			// Se for produto variável sem variação especificada, usa a primeira variação disponível para cotação de peso/valor.
			if ( $product->is_type( 'variable' ) ) {
				$children = $product->get_children();
				if ( ! empty( $children[0] ) ) {
					$first_var = wc_get_product( $children[0] );
					if ( $first_var instanceof WC_Product ) {
						$product = $first_var;
					}
				}
			}

			$line_total = (float) $product->get_price() * $qty;
			$package    = array(
				'contents'        => array(
					'preview_item' => array(
						'data'              => $product,
						'quantity'          => $qty,
						'line_total'        => $line_total,
						'line_subtotal'     => $line_total,
						'line_tax'          => 0,
						'line_subtotal_tax' => 0,
						'product_id'        => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
						'variation_id'      => $product->is_type( 'variation' ) ? $product->get_id() : 0,
						'variation'         => array(),
					),
				),
				'contents_cost'   => $line_total,
				'applied_coupons' => array(),
				'user'            => array( 'ID' => get_current_user_id() ),
				'destination'     => $destination,
				'cart_subtotal'   => $line_total,
			);

			WC()->shipping()->reset_shipping();
			$calculated = WC()->shipping()->calculate_shipping_for_package( $package );
			$raw_rates  = ! empty( $calculated['rates'] ) && is_array( $calculated['rates'] ) ? $calculated['rates'] : array();
		} else {
			if ( WC()->customer ) {
				WC()->customer->set_location( 'BR', $state, $formatted_cep, $city );
				WC()->customer->set_shipping_location( 'BR', $state, $formatted_cep, $city );
				WC()->customer->save();
			}
			WC()->shipping()->reset_shipping();
			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();

			$packages  = WC()->shipping()->get_packages();
			$raw_rates = ! empty( $packages[0]['rates'] ) ? $packages[0]['rates'] : array();
		}

		foreach ( $raw_rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}
			$cost        = (float) $rate->get_cost();
			$rates_out[] = array(
				'id'         => $rate->get_id(),
				'method_id'  => $rate->get_method_id(),
				'label'      => $rate->get_label(),
				'cost'       => $cost,
				'cost_label' => $cost <= 0.0 ? 'GRÁTIS' : store_format_brl( $cost ),
				'is_free'    => $cost <= 0.0,
			);
		}

		if ( empty( $rates_out ) ) {
			wp_send_json_error(
				array(
					'message' => 'Nenhuma opção de entrega encontrada para o CEP informado.',

				),
				404
			);
		}

		wp_send_json_success(
			array(
				'cep'     => $formatted_cep,
				'address' => $address,
				'rates'   => $rates_out,
			)
		);
	}
}
