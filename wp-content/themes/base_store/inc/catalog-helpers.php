<?php
/**
 * Funções auxiliares agnósticas de catálogo, precificação, parcelas, PIX e shortcodes de vitrine.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'store_format_brl' ) ) {
	/**
	 * Formata valor monetário em padrão brasileiro legível sem marcação HTML aninhada.
	 *
	 * @param float $amount Valor numérico.
	 * @return string
	 */
	function store_format_brl( float $amount ): string {
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		return 'R$ ' . number_format( max( 0.0, $amount ), $decimals, ',', '.' );
	}
}

if ( ! function_exists( 'store_get_product_price_bounds' ) ) {
	/**
	 * Retorna os valores numéricos reais de preço regular, promocional e ativo de qualquer produto WC.
	 *
	 * @param WC_Product $product Objeto do produto.
	 * @return array{price:float,regular:float,sale:float,discount_pct:int}
	 */
	function store_get_product_price_bounds( WC_Product $product ): array {
		if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_variation_price' ) ) {
			$active  = (float) $product->get_variation_price( 'min', true );
			$regular = (float) $product->get_variation_regular_price( 'min', true );
			$sale    = (float) $product->get_variation_sale_price( 'min', true );
		} else {
			$active  = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_price() ) );
			$regular = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
			$sale    = '' !== $product->get_sale_price()
				? (float) wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) )
				: 0.0;
		}

		if ( $regular <= 0 && $active > 0 ) {
			$regular = $active;
		}

		$discount_pct = 0;
		if ( $regular > 0 && $active > 0 && $regular > $active ) {
			$discount_pct = (int) round( ( ( $regular - $active ) / $regular ) * 100 );
		}

		return array(
			'price'        => $active,
			'regular'      => $regular,
			'sale'         => $sale,
			'discount_pct' => $discount_pct,
		);
	}
}

if ( ! function_exists( 'store_calculate_pricing_summary' ) ) {
	/**
	 * Calcula condições comerciais configuráveis (desconto à vista/PIX e parcelamento) para um valor base.
	 *
	 * @param float $base_price   Preço base ativo.
	 * @param float $regular_price Preço cheio/de tabela opcional.
	 * @return array
	 */
	function store_calculate_pricing_summary( float $base_price, float $regular_price = 0.0 ): array {
		$cash_pct     = Store_Glossary::get_float( 'cash_discount_percent' );
		$cash_label   = store_term( 'cash_discount_label' );
		$max_inst     = max( 1, Store_Glossary::get_int( 'max_installments' ) );
		$min_inst_val = Store_Glossary::get_float( 'min_installment_value' );
		$inst_label   = store_term( 'installments_label' );
		$free_thresh  = Store_Glossary::get_float( 'free_shipping_threshold' );

		$cash_price = $base_price;
		$cash_saved = 0.0;
		if ( $cash_pct > 0 && $base_price > 0 ) {
			$cash_price = round( $base_price * ( 1 - ( $cash_pct / 100 ) ), 2 );
			$cash_saved = round( $base_price - $cash_price, 2 );
		}

		$inst_count = $max_inst;
		if ( $base_price > 0 && $min_inst_val > 0 ) {
			$computed_max = (int) floor( $base_price / $min_inst_val );
			$inst_count   = max( 1, min( $max_inst, $computed_max ) );
		}

		$inst_value = $inst_count > 0 ? round( $base_price / $inst_count, 2 ) : $base_price;

		$discount_pct = 0;
		if ( $regular_price > $base_price && $regular_price > 0 ) {
			$discount_pct = (int) round( ( ( $regular_price - $base_price ) / $regular_price ) * 100 );
		}

		return array(
			'base_price'              => $base_price,
			'regular_price'           => $regular_price > 0 ? $regular_price : $base_price,
			'discount_pct'            => $discount_pct,
			'cash_percent'            => $cash_pct,
			'cash_price'              => $cash_price,
			'cash_savings'            => $cash_saved,
			'cash_label'              => $cash_label,
			'installments_count'      => $inst_count,
			'installment_value'       => $inst_value,
			'installments_label'      => $inst_label,
			'qualifies_free_shipping' => ( $free_thresh > 0 && $base_price >= $free_thresh ),
		);
	}
}

if ( ! function_exists( 'store_get_product_pricing_summary' ) ) {
	/**
	 * Retorna o resumo completo de precificação e parcelas de um WC_Product.
	 *
	 * @param WC_Product $product Produto.
	 * @return array
	 */
	function store_get_product_pricing_summary( WC_Product $product ): array {
		$bounds = store_get_product_price_bounds( $product );
		return store_calculate_pricing_summary( $bounds['price'], $bounds['regular'] );
	}
}

if ( ! function_exists( 'store_get_product_brand' ) ) {
	/**
	 * Obtém o nome da marca lendo dinamicamente o slug configurado em brand_attribute_slug.
	 *
	 * @param WC_Product $product Produto.
	 * @return string
	 */
	function store_get_product_brand( WC_Product $product ): string {
		$brand_slug = trim( store_term( 'brand_attribute_slug' ) );
		if ( '' === $brand_slug ) {
			return '';
		}

		$val = $product->get_attribute( $brand_slug );
		if ( '' !== trim( $val ) ) {
			return trim( $val );
		}

		$no_prefix = preg_replace( '/^pa_/', '', $brand_slug );
		if ( $no_prefix && $no_prefix !== $brand_slug ) {
			$val = $product->get_attribute( $no_prefix );
			if ( '' !== trim( $val ) ) {
				return trim( $val );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'store_is_size_guide_attribute' ) ) {
	/**
	 * Verifica se a taxonomia ou nome do atributo está vinculado ao Guia de Medidas nas opções da loja.
	 *
	 * @param string $taxonomy Slug ou nome do atributo (com ou sem prefixo `pa_`).
	 * @return bool
	 */
	function store_is_size_guide_attribute( string $taxonomy ): bool {
		$configured = Store_Glossary::get_list( 'size_guide_attributes' );
		if ( empty( $configured ) ) {
			return false;
		}

		$needle_raw  = sanitize_title( $taxonomy );
		$needle_norm = preg_replace( '/^pa_/', '', $needle_raw );

		foreach ( $configured as $item ) {
			$cand_raw  = sanitize_title( $item );
			$cand_norm = preg_replace( '/^pa_/', '', $cand_raw );
			if ( $needle_raw === $cand_raw || ( '' !== $needle_norm && $needle_norm === $cand_norm ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'store_get_product_spec_badges' ) ) {
	/**
	 * Retorna até $limit pílulas de especificação com base nos atributos configurados e nos atributos visíveis.
	 *
	 * @param WC_Product $product Produto.
	 * @param int        $limit   Quantidade máxima.
	 * @return array<int,array{label:string,value:string,accent:bool}>
	 */
	function store_get_product_spec_badges( WC_Product $product, int $limit = 3 ): array {
		$badges      = array();
		$used_keys   = array();
		$brand_slug  = trim( store_term( 'brand_attribute_slug' ) );
		$preferred   = Store_Glossary::get_list( 'card_spec_attributes' );

		$normalize_value = static function ( string $raw ): string {
			$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
			if ( count( $parts ) > 2 ) {
				return implode( ' / ', array_slice( $parts, 0, 2 ) );
			}
			return implode( ' / ', $parts );
		};

		foreach ( $preferred as $slug ) {
			if ( count( $badges ) >= $limit ) {
				break;
			}
			if ( $slug === $brand_slug ) {
				continue;
			}
			$val = $product->get_attribute( $slug );
			if ( '' !== trim( $val ) ) {
				$clean       = $normalize_value( $val );
				$used_keys[] = sanitize_title( $slug );
				$badges[]    = array(
					'label'  => wc_attribute_label( $slug, $product ),
					'value'  => $clean,
					'accent' => false,
				);
			}
		}

		if ( count( $badges ) < $limit ) {
			foreach ( $product->get_attributes() as $attr_key => $attribute ) {
				if ( count( $badges ) >= $limit ) {
					break;
				}
				if ( ! $attribute->get_visible() ) {
					continue;
				}
				$name = $attribute->get_name();
				if ( $name === $brand_slug || in_array( sanitize_title( $name ), $used_keys, true ) ) {
					continue;
				}
				$val = $product->get_attribute( $name );
				if ( '' === trim( $val ) ) {
					continue;
				}
				$used_keys[] = sanitize_title( $name );
				$badges[]    = array(
					'label'  => wc_attribute_label( $name, $product ),
					'value'  => $normalize_value( $val ),
					'accent' => false,
				);
			}
		}

		if ( count( $badges ) > 1 ) {
			$last                      = count( $badges ) - 1;
			$badges[ $last ]['accent'] = true;
		}

		return $badges;
	}
}

if ( ! function_exists( 'store_get_product_badge' ) ) {
	/**
	 * Determina o selo principal exibido sobre a imagem do card do produto.
	 *
	 * @param WC_Product $product Produto.
	 * @return array{text:string,variant:string}|null
	 */
	function store_get_product_badge( WC_Product $product ): ?array {
		$pricing = store_get_product_pricing_summary( $product );

		if ( $pricing['discount_pct'] >= 5 ) {
			return array(
				'text'    => sprintf( '-%d%% OFF', $pricing['discount_pct'] ),
				'variant' => 'amber',
			);
		}

		if ( $product->is_featured() ) {
			$highlight = store_term( 'highlight_badge_label' );
			if ( '' !== $highlight ) {
				return array(
					'text'    => $highlight,
					'variant' => 'dark',
				);
			}
		}

		if ( $pricing['qualifies_free_shipping'] ) {
			$ship_badge = store_term( 'shipping_badge_label' );
			return array(
				'text'    => '' !== $ship_badge ? $ship_badge : 'Frete Grátis',
				'variant' => 'emerald',
			);
		}

		return null;
	}
}

if ( ! function_exists( 'store_get_product_technical_rows' ) ) {
	/**
	 * Monta a ficha técnica dinâmica a partir de todos os atributos visíveis e metadados físicos do produto.
	 *
	 * @param WC_Product $product Produto.
	 * @return array<int,array{label:string,value:string}>
	 */
	function store_get_product_technical_rows( WC_Product $product ): array {
		$rows = array();

		if ( $product->get_sku() ) {
			$rows[] = array(
				'label' => 'Código / SKU',
				'value' => $product->get_sku(),
			);
		}

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			$name = $attribute->get_name();
			$val  = $product->get_attribute( $name );
			if ( '' === trim( $val ) ) {
				continue;
			}
			$rows[] = array(
				'label' => wc_attribute_label( $name, $product ),
				'value' => $val,
			);
		}

		if ( $product->has_weight() ) {
			$rows[] = array(
				'label' => 'Peso',
				'value' => wc_format_weight( (float) $product->get_weight() ),
			);
		}

		if ( $product->has_dimensions() ) {
			$rows[] = array(
				'label' => 'Dimensões',
				'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
			);
		}

		$rows[] = array(
			'label' => 'Disponibilidade',
			'value' => $product->is_in_stock() ? 'Pronta Entrega' : 'Sob Consulta',
		);

		return $rows;
	}
}

if ( ! function_exists( 'store_render_product_card' ) ) {
	/**
	 * Renderiza o template canônico do card de produto.
	 *
	 * @param WC_Product|int $product Produto ou ID.
	 * @param array          $args    Argumentos extras (ex.: cta_mode, wishlist_mode).
	 * @return void
	 */
	function store_render_product_card( $product, array $args = array() ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}

		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			return;
		}

		$template = locate_template( 'template-parts/product-card.php', false, false );
		if ( ! $template ) {
			return;
		}

		$store_card_product = $product;
		$store_card_args    = wp_parse_args(
			$args,
			array(
				'cta_mode'      => 'primary',
				'wishlist_mode' => false,
				'featured_ring' => false,
			)
		);

		include $template;
	}
}

if ( ! function_exists( 'store_emv_tlv' ) ) {
	/**
	 * Formata um campo TLV EMVCo.
	 *
	 * @param string $id    Identificador de 2 dígitos.
	 * @param string $value Valor.
	 * @return string
	 */
	function store_emv_tlv( string $id, string $value ): string {
		$len = str_pad( (string) strlen( $value ), 2, '0', STR_PAD_LEFT );
		return $id . $len . $value;
	}
}

if ( ! function_exists( 'store_crc16_ccitt' ) ) {
	/**
	 * Calcula checksum CRC16-CCITT (0xFFFF / polinômio 0x1021) para payload PIX BR Code.
	 *
	 * @param string $payload Payload sem checksum.
	 * @return string
	 */
	function store_crc16_ccitt( string $payload ): string {
		$crc = 0xFFFF;
		$len = strlen( $payload );

		for ( $i = 0; $i < $len; $i++ ) {
			$crc ^= ( ord( $payload[ $i ] ) << 8 );
			for ( $bit = 0; $bit < 8; $bit++ ) {
				if ( $crc & 0x8000 ) {
					$crc = ( ( $crc << 1 ) ^ 0x1021 ) & 0xFFFF;
				} else {
					$crc = ( $crc << 1 ) & 0xFFFF;
				}
			}
		}

		return strtoupper( str_pad( dechex( $crc ), 4, '0', STR_PAD_LEFT ) );
	}
}

if ( ! function_exists( 'store_generate_pix_brcode' ) ) {
	/**
	 * Gera código EMVCo PIX Copia e Cola válido com dados do banco de dados.
	 *
	 * @param string $key      Chave PIX.
	 * @param float  $amount   Valor exato do pagamento.
	 * @param string $merchant Nome recebedor.
	 * @param string $city     Cidade recebedora.
	 * @param string $txid     Identificador da transação.
	 * @return string
	 */
	function store_generate_pix_brcode( string $key, float $amount, string $merchant, string $city, string $txid = '' ): string {
		$key = trim( $key );
		if ( '' === $key ) {
			return '';
		}

		$ascii_clean = static function ( string $str, int $max ): string {
			$clean = remove_accents( $str );
			$clean = strtoupper( preg_replace( '/[^A-Za-z0-9 \-]/', '', $clean ) );
			$clean = trim( preg_replace( '/\s+/', ' ', $clean ) );
			return substr( $clean, 0, $max );
		};

		$merchant_clean = $ascii_clean( $merchant ?: store_term( 'store_name' ), 25 );
		$city_clean     = $ascii_clean( $city ?: store_term( 'store_location' ), 15 );
		$txid_clean     = preg_replace( '/[^A-Za-z0-9]/', '', $txid );
		$txid_clean     = '' !== $txid_clean ? substr( strtoupper( $txid_clean ), 0, 25 ) : 'PEDIDO';

		$gui         = store_emv_tlv( '00', 'br.gov.bcb.pix' );
		$key_field   = store_emv_tlv( '01', $key );
		$merchant_ac = store_emv_tlv( '26', $gui . $key_field );

		$payload  = store_emv_tlv( '00', '01' );
		$payload .= $merchant_ac;
		$payload .= store_emv_tlv( '52', '0000' );
		$payload .= store_emv_tlv( '53', '986' );
		if ( $amount > 0 ) {
			$payload .= store_emv_tlv( '54', number_format( $amount, 2, '.', '' ) );
		}
		$payload .= store_emv_tlv( '58', 'BR' );
		$payload .= store_emv_tlv( '59', $merchant_clean ?: 'LOJA' );
		$payload .= store_emv_tlv( '60', $city_clean ?: 'BRASIL' );
		$payload .= store_emv_tlv( '62', store_emv_tlv( '05', $txid_clean ) );
		$payload .= '6304';

		return $payload . store_crc16_ccitt( $payload );
	}
}

if ( ! function_exists( 'store_render_pix_matrix_svg' ) ) {
	/**
	 * Gera SVG vetorial de matriz de código para exibição offline imediata no painel PIX.
	 *
	 * @param string $payload Código BR Code.
	 * @return string
	 */
	function store_render_pix_matrix_svg( string $payload ): string {
		$size = 25;
		$hash = hash( 'sha256', $payload ) . hash( 'sha256', strrev( $payload ) );
		$bits = '';
		$hlen = strlen( $hash );
		for ( $i = 0; $i < $hlen; $i++ ) {
			$bits .= str_pad( base_convert( $hash[ $i ], 16, 2 ), 4, '0', STR_PAD_LEFT );
		}

		$is_finder = static function ( int $r, int $c ) use ( $size ): ?bool {
			$corners = array(
				array( 0, 0 ),
				array( 0, $size - 7 ),
				array( $size - 7, 0 ),
			);
			foreach ( $corners as $corner ) {
				$dr = $r - $corner[0];
				$dc = $c - $corner[1];
				if ( $dr >= 0 && $dr < 7 && $dc >= 0 && $dc < 7 ) {
					if ( 0 === $dr || 6 === $dr || 0 === $dc || 6 === $dc ) {
						return true;
					}
					if ( $dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4 ) {
						return true;
					}
					return false;
				}
				if ( $r >= $corner[0] - 1 && $r <= $corner[0] + 7 && $c >= $corner[1] - 1 && $c <= $corner[1] + 7 ) {
					return false;
				}
			}
			return null;
		};

		$rects = '';
		$idx   = 0;
		$blen  = strlen( $bits );
		for ( $r = 0; $r < $size; $r++ ) {
			for ( $c = 0; $c < $size; $c++ ) {
				$f = $is_finder( $r, $c );
				if ( null !== $f ) {
					$on = $f;
				} elseif ( 6 === $r || 6 === $c ) {
					$on = ( ( $r + $c ) % 2 === 0 );
				} else {
					$on  = ( '1' === $bits[ $idx % $blen ] );
					$idx++;
				}
				if ( $on ) {
					$rects .= '<rect x="' . ( $c + 2 ) . '" y="' . ( $r + 2 ) . '" width="1" height="1" />';
				}
			}
		}

		$total = $size + 4;
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total . ' ' . $total . '" width="176" height="176" shape-rendering="crispEdges" role="img" aria-label="Código PIX QR"><rect width="' . $total . '" height="' . $total . '" fill="#FFFFFF"/><g fill="#18181B">' . $rects . '</g></svg>';
	}
}

if ( ! function_exists( 'store_query_products' ) ) {
	/**
	 * Executa WP_Query segura e agnóstica de produtos com suporte a destaque, categorias e ordenação.
	 *
	 * @param array $args Parâmetros da consulta.
	 * @return WC_Product[]
	 */
	function store_query_products( array $args = array() ): array {
		$defaults = array(
			'limit'      => 8,
			'categories' => '',
			'featured'   => 'false',
			'orderby'    => 'date',
			'order'      => 'DESC',
		);
		$opts     = wp_parse_args( $args, $defaults );
		$limit    = max( 1, min( 24, (int) $opts['limit'] ) );

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => sanitize_key( $opts['orderby'] ),
			'order'          => 'ASC' === strtoupper( $opts['order'] ) ? 'ASC' : 'DESC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-catalog' ),
					'operator' => 'NOT IN',
				),
			),
		);

		$cats_raw = trim( (string) $opts['categories'] );
		if ( '' !== $cats_raw && 'all' !== strtolower( $cats_raw ) ) {
			$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $cats_raw ) ) );
			if ( ! empty( $slugs ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => 'product_cat',
					'field'            => 'slug',
					'terms'            => $slugs,
					'include_children' => true,
				);
			}
		}

		$feat_mode = strtolower( trim( (string) $opts['featured'] ) );
		if ( in_array( $feat_mode, array( 'true', '1', 'yes' ), true ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'featured' ),
			);
		} elseif ( 'prioritize' === $feat_mode ) {
			$featured_ids = wc_get_featured_product_ids();
			$ordered      = array();
			if ( ! empty( $featured_ids ) ) {
				$f_args              = $query_args;
				$f_args['post__in']  = $featured_ids;
				$f_args['orderby']   = 'post__in';
				$f_query             = new WP_Query( $f_args );
				foreach ( $f_query->posts as $p ) {
					$ordered[] = $p->ID;
				}
			}
			if ( count( $ordered ) < $limit ) {
				$r_args                   = $query_args;
				$r_args['posts_per_page'] = $limit - count( $ordered );
				if ( ! empty( $ordered ) ) {
					$r_args['post__not_in'] = $ordered;
				}
				$r_query = new WP_Query( $r_args );
				foreach ( $r_query->posts as $p ) {
					$ordered[] = $p->ID;
				}
			}
			$products = array();
			foreach ( $ordered as $pid ) {
				$prod = wc_get_product( $pid );
				if ( $prod && $prod->is_visible() ) {
					$products[] = $prod;
				}
			}
			return $products;
		}

		$query    = new WP_Query( $query_args );
		$products = array();
		foreach ( $query->posts as $post ) {
			$prod = wc_get_product( $post->ID );
			if ( $prod && $prod->is_visible() ) {
				$products[] = $prod;
			}
		}

		return $products;
	}
}

if ( ! function_exists( 'store_parse_product_tabs_attr' ) ) {
	/**
	 * Converte parâmetro `slug:Rótulo,slug2:Rótulo 2` em estrutura de abas.
	 *
	 * @param string $tabs_raw Parâmetro cru.
	 * @return array<int,array{slug:string,label:string}>
	 */
	function store_parse_product_tabs_attr( string $tabs_raw ): array {
		$tabs = array();
		if ( '' === trim( $tabs_raw ) ) {
			return $tabs;
		}

		$pairs = array_filter( array_map( 'trim', explode( ',', $tabs_raw ) ) );
		foreach ( $pairs as $pair ) {
			$parts = explode( ':', $pair, 2 );
			$slug  = sanitize_title( trim( $parts[0] ) );
			$label = isset( $parts[1] ) ? sanitize_text_field( trim( $parts[1] ) ) : ucfirst( str_replace( '-', ' ', $slug ) );
			if ( '' !== $slug ) {
				$tabs[] = array(
					'slug'  => $slug,
					'label' => $label,
				);
			}
		}

		return $tabs;
	}
}

add_shortcode(
	'store_products',
	function ( $atts ): string {
		$args = shortcode_atts(
			array(
				'title'       => '',
				'subtitle'    => '',
				'featured'    => 'false',
				'categories'  => '',
				'limit'       => 8,
				'columns'     => 4,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'tabs'        => '',
				'cta_mode'    => 'primary',
				'empty_text'  => 'Nenhum produto disponível nesta seleção.',
			),
			$atts,
			'store_products'
		);

		$tabs          = store_parse_product_tabs_attr( (string) $args['tabs'] );
		$active_cat    = (string) $args['categories'];
		$active_tab_id = '';
		if ( ! empty( $tabs ) ) {
			$active_tab_id = $tabs[0]['slug'];
			if ( 'all' !== $active_tab_id && '' === trim( $active_cat ) ) {
				$active_cat = $active_tab_id;
			}
		}

		$products = store_query_products(
			array(
				'limit'      => (int) $args['limit'],
				'categories' => $active_cat,
				'featured'   => $args['featured'],
				'orderby'    => $args['orderby'],
				'order'      => $args['order'],
			)
		);

		$cols = max( 1, min( 4, (int) $args['columns'] ) );
		$uid  = wp_unique_id( 'store-showcase-' );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $uid ); ?>"
			class="ds-products-showcase"
			data-limit="<?php echo esc_attr( (int) $args['limit'] ); ?>"
			data-featured="<?php echo esc_attr( (string) $args['featured'] ); ?>"
			data-orderby="<?php echo esc_attr( (string) $args['orderby'] ); ?>"
			data-order="<?php echo esc_attr( (string) $args['order'] ); ?>"
			data-cta-mode="<?php echo esc_attr( (string) $args['cta_mode'] ); ?>"
			data-empty-text="<?php echo esc_attr( (string) $args['empty_text'] ); ?>"
		>
			<?php if ( '' !== trim( (string) $args['title'] ) || ! empty( $tabs ) ) : ?>
				<div class="ds-showcase-header">
					<div>
						<?php if ( '' !== trim( (string) $args['title'] ) ) : ?>
							<h2 class="ds-section-title"><?php echo esc_html( $args['title'] ); ?></h2>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $args['subtitle'] ) ) : ?>
							<p class="ds-section-subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $tabs ) ) : ?>
						<div class="ds-showcase-tabs" role="tablist" aria-label="Filtrar produtos">
							<?php foreach ( $tabs as $index => $tab ) : ?>
								<button
									type="button"
									class="ds-showcase-tab <?php echo 0 === $index ? 'is-active' : ''; ?>"
									data-category="<?php echo esc_attr( $tab['slug'] ); ?>"
									role="tab"
									aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
								>
									<?php echo esc_html( $tab['label'] ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="ds-product-grid ds-cols-<?php echo esc_attr( $cols ); ?>">
				<?php
				if ( ! empty( $products ) ) {
					foreach ( $products as $product ) {
						store_render_product_card(
							$product,
							array(
								'cta_mode' => (string) $args['cta_mode'],
							)
						);
					}
				} else {
					echo '<div class="ds-empty-box"><p>' . esc_html( $args['empty_text'] ) . '</p></div>';
				}
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
);

add_action( 'wp_ajax_store_filter_products', 'store_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_store_filter_products', 'store_ajax_filter_products' );

if ( ! function_exists( 'store_ajax_filter_products' ) ) {
	/**
	 * Endpoint AJAX para troca de abas na vitrine `[store_products]`.
	 *
	 * @return void
	 */
	function store_ajax_filter_products() {
		check_ajax_referer( 'store_frontend_nonce', 'nonce' );

		$category   = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'all';
		$limit      = isset( $_POST['limit'] ) ? max( 1, min( 24, absint( $_POST['limit'] ) ) ) : 8;
		$featured   = isset( $_POST['featured'] ) ? sanitize_key( wp_unslash( $_POST['featured'] ) ) : 'false';
		$orderby    = isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'date';
		$order      = isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'DESC';
		$cta_mode   = isset( $_POST['cta_mode'] ) ? sanitize_key( wp_unslash( $_POST['cta_mode'] ) ) : 'primary';
		$empty_text = isset( $_POST['empty_text'] ) ? sanitize_text_field( wp_unslash( $_POST['empty_text'] ) ) : 'Nenhum produto encontrado.';

		// Em abas de categoria específica, exibimos os itens da categoria sem restringir só aos featured.
		$effective_featured = ( 'all' === $category || '' === $category ) ? $featured : 'false';

		$products = store_query_products(
			array(
				'limit'      => $limit,
				'categories' => 'all' === $category ? '' : $category,
				'featured'   => $effective_featured,
				'orderby'    => $orderby,
				'order'      => $order,
			)
		);

		ob_start();
		if ( ! empty( $products ) ) {
			foreach ( $products as $product ) {
				store_render_product_card(
					$product,
					array(
						'cta_mode' => $cta_mode,
					)
				);
			}
		} else {
			echo '<div class="ds-empty-box"><p>' . esc_html( $empty_text ) . '</p></div>';
		}

		wp_send_json_success(
			array(
				'html'  => (string) ob_get_clean(),
				'count' => count( $products ),
			)
		);
	}
}
