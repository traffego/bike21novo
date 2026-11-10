<?php
/**
 * Funções e definições do tema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Previne acesso direto.
}

// 1. Carrega o Painel de Opções, Glossário de Termos e módulos funcionais da loja.
require_once get_stylesheet_directory() . '/inc/theme-options.php';
require_once get_stylesheet_directory() . '/inc/catalog-helpers.php';
require_once get_stylesheet_directory() . '/inc/catalog-filters.php';
require_once get_stylesheet_directory() . '/inc/wishlist.php';
require_once get_stylesheet_directory() . '/inc/size-guide.php';
require_once get_stylesheet_directory() . '/inc/shipping-cep.php';
require_once get_stylesheet_directory() . '/inc/contact-form.php';
require_once get_stylesheet_directory() . '/inc/order-tracking.php';
require_once get_stylesheet_directory() . '/inc/checkout-br.php';

/**
 * Declara suporte nativo a recursos WooCommerce no tema filho.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

/**
 * Enfileira fontes, folhas de estilo do Design System e script interativo unificado.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		// 1. Google Fonts: Inter (400, 500, 600, 700, 800, 900)
		wp_enqueue_style(
			'font-inter',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
			array(),
			null
		);

		// 2. Estilo do tema pai
		wp_enqueue_style(
			'parent-theme-style',
			get_template_directory_uri() . '/style.css'
		);

		// 3. Estilo do tema filho (Design System Tokens)
		wp_enqueue_style(
			'child-theme-style',
			get_stylesheet_uri(),
			array( 'font-inter', 'parent-theme-style' ),
			wp_get_theme()->get( 'Version' )
		);

		// 4. Estilos dos componentes agnósticos da loja
		$comp_css = $dir . '/assets/css/store-components.css';
		if ( file_exists( $comp_css ) ) {
			wp_enqueue_style(
				'store-components',
				$uri . '/assets/css/store-components.css',
				array( 'child-theme-style' ),
				(string) filemtime( $comp_css )
			);
		}

		// 5. JavaScript interativo desacoplado e configurações locais
		$front_js = $dir . '/assets/js/store-frontend.js';
		if ( file_exists( $front_js ) ) {
			$deps = class_exists( 'WooCommerce' ) ? array( 'jquery' ) : array();
			if ( function_exists( 'is_product' ) && is_product() ) {
				wp_enqueue_script( 'wc-add-to-cart-variation' );
			}

			wp_enqueue_script(
				'store-frontend',
				$uri . '/assets/js/store-frontend.js',
				$deps,
				(string) filemtime( $front_js ),
				true
			);

			$cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;

			wp_localize_script(
				'store-frontend',
				'StoreConfig',
				array(
					'ajax_url'                => admin_url( 'admin-ajax.php' ),
					'nonce'                   => wp_create_nonce( 'store_frontend_nonce' ),
					'wishlist_count'          => function_exists( 'store_get_wishlist_count' ) ? store_get_wishlist_count() : 0,
					'cart_count'              => $cart_count,
					'cash_discount_percent'   => Store_Glossary::get_float( 'cash_discount_percent' ),
					'cash_discount_label'     => store_term( 'cash_discount_label' ),
					'pix_gateway_ids'         => Store_Glossary::get_list( 'pix_gateway_ids' ),
					'max_installments'        => max( 1, Store_Glossary::get_int( 'max_installments' ) ),
					'min_installment_value'   => Store_Glossary::get_float( 'min_installment_value' ),
					'installments_label'      => store_term( 'installments_label' ),
					'free_shipping_threshold' => Store_Glossary::get_float( 'free_shipping_threshold' ),
				)
			);
		}
	},
	20
);

/**
 * Registra tipos de documento 'header' e 'footer' no Elementor Free
 * caso nenhum módulo externo já os tenha registrado.
 */
add_action(
	'elementor/documents/register',
	function ( $documents_manager ) {
		if ( ! class_exists( '\\Elementor\\Modules\\Library\\Documents\\Section' ) ) {
			return;
		}

		if ( ! $documents_manager->get_document_type( 'header', false ) ) {
			if ( ! class_exists( 'Store_Header_Document' ) ) {
				class Store_Header_Document extends \Elementor\Modules\Library\Documents\Section {
					public static function get_properties() {
						$properties                  = parent::get_properties();
						$properties['cpt']           = array();
						$properties['register_type'] = false;
						return $properties;
					}
					public static function get_type() {
						return 'header';
					}
					public static function get_title() {
						return 'Header';
					}
					public static function get_plural_title() {
						return 'Headers';
					}
				}
			}
			$documents_manager->register_document_type( 'header', 'Store_Header_Document' );
		}

		if ( ! $documents_manager->get_document_type( 'footer', false ) ) {
			if ( ! class_exists( 'Store_Footer_Document' ) ) {
				class Store_Footer_Document extends \Elementor\Modules\Library\Documents\Section {
					public static function get_properties() {
						$properties                  = parent::get_properties();
						$properties['cpt']           = array();
						$properties['register_type'] = false;
						return $properties;
					}
					public static function get_type() {
						return 'footer';
					}
					public static function get_title() {
						return 'Footer';
					}
					public static function get_plural_title() {
						return 'Footers';
					}
				}
			}
			$documents_manager->register_document_type( 'footer', 'Store_Footer_Document' );
		}
	}
);

/**
 * Suporte a shortcodes do glossário em todos os widgets do Elementor (títulos, links, botões, listas e icon boxes).
 */
add_action(
	'elementor/frontend/widget/before_render',
	function ( $widget ) {
		$settings = $widget->get_settings();
		$changed  = false;

		if ( ! empty( $settings['link']['url'] ) && strpos( $settings['link']['url'], '[store_term' ) !== false ) {
			$settings['link']['url'] = do_shortcode( $settings['link']['url'] );
			$changed                 = true;
		}

		foreach ( array( 'title', 'text', 'title_text', 'description_text', 'editor' ) as $field ) {
			if ( ! empty( $settings[ $field ] ) && is_string( $settings[ $field ] ) && strpos( $settings[ $field ], '[store_term' ) !== false ) {
				$settings[ $field ] = do_shortcode( $settings[ $field ] );
				$changed            = true;
			}
		}

		if ( ! empty( $settings['icon_list'] ) && is_array( $settings['icon_list'] ) ) {
			foreach ( $settings['icon_list'] as $idx => $item ) {
				if ( ! empty( $item['text'] ) && is_string( $item['text'] ) && strpos( $item['text'], '[store_term' ) !== false ) {
					$settings['icon_list'][ $idx ]['text'] = do_shortcode( $item['text'] );
					$changed                               = true;
				}
				if ( ! empty( $item['link']['url'] ) && is_string( $item['link']['url'] ) && strpos( $item['link']['url'], '[store_term' ) !== false ) {
					$settings['icon_list'][ $idx ]['link']['url'] = do_shortcode( $item['link']['url'] );
					$changed                                      = true;
				}
			}
		}

		if ( $changed ) {
			$widget->set_settings( $settings );
		}
	}
);

add_filter(
	'elementor/widget/render_content',
	function ( string $content, $widget ): string {
		if ( is_object( $widget ) && method_exists( $widget, 'get_name' ) && 'shortcode' === $widget->get_name() ) {
			return $content;
		}
		if ( false !== strpos( $content, '[store_term' ) ) {
			return do_shortcode( $content );
		}
		return $content;
	},
	10,
	2
);

/**
 * Estiliza e traduz o formulário nativo de busca de produtos do WooCommerce
 * utilizando placeholder configurável via Store_Glossary.
 */
add_filter(
	'get_product_search_form',
	function () {
		$action      = esc_url( home_url( '/' ) );
		$value       = esc_attr( get_search_query() );
		$placeholder = store_term( 'search_placeholder' );
		if ( '' === $placeholder ) {
			$placeholder = 'Buscar produtos na loja...';
		}

		return '<form role="search" method="get" class="woocommerce-product-search" action="' . $action . '">' .
			'<span class="ds-search-icon" aria-hidden="true">' .
			'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>' .
			'</span>' .
			'<label class="screen-reader-text" for="wc-product-search-field">Buscar produtos</label>' .
			'<input type="search" id="wc-product-search-field" class="search-field" placeholder="' . esc_attr( $placeholder ) . '" value="' . $value . '" name="s" />' .
			'<button type="submit" value="Buscar">Buscar</button>' .
			'<input type="hidden" name="post_type" value="product" />' .
			'</form>';
	}
);

if ( ! function_exists( 'store_get_active_template_id' ) ) {
	/**
	 * Resolve dinamicamente o ID do modelo ativo ('header' ou 'footer') para a requisição atual
	 * consultando a biblioteca do Elementor (_elementor_template_type) e validando _elementor_conditions.
	 *
	 * @param string $template_type Tipo de modelo ('header' ou 'footer').
	 * @return int ID do modelo publicado mais específico para a requisição atual, ou 0.
	 */
	function store_get_active_template_id( string $template_type ): int {
		if ( ! in_array( $template_type, array( 'header', 'footer' ), true ) ) {
			return 0;
		}

		// Evita recursão ao visualizar ou editar modelos de biblioteca diretamente.
		if ( is_singular( array( 'elementor_library', 'emcp_theme_template' ) ) ) {
			return 0;
		}

		static $resolved = array();

		if ( did_action( 'wp' ) && array_key_exists( $template_type, $resolved ) ) {
			return $resolved[ $template_type ];
		}

		$templates = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_key'       => '_elementor_template_type',
				'meta_value'     => $template_type,
				'fields'         => 'ids',
			)
		);

		if ( empty( $templates ) ) {
			if ( did_action( 'wp' ) ) {
				$resolved[ $template_type ] = 0;
			}
			return 0;
		}

		$front_match   = 0;
		$page_match    = 0;
		$general_match = 0;

		foreach ( $templates as $tpl_id ) {
			$tpl_id     = (int) $tpl_id;
			$conditions = get_post_meta( $tpl_id, '_elementor_conditions', true );
			if ( ! is_array( $conditions ) ) {
				continue;
			}

			foreach ( $conditions as $cond ) {
				if ( 'include/general/front_page' === $cond && is_front_page() && 0 === $front_match ) {
					$front_match = $tpl_id;
				} elseif ( 'include/singular/page' === $cond && is_page() && 0 === $page_match ) {
					$page_match = $tpl_id;
				} elseif ( 'include/general' === $cond && 0 === $general_match ) {
					$general_match = $tpl_id;
				}
			}
		}

		$match = $front_match ? $front_match : ( $page_match ? $page_match : $general_match );

		if ( did_action( 'wp' ) ) {
			$resolved[ $template_type ] = $match;
		}

		return $match;
	}
}

if ( ! function_exists( 'store_render_active_template' ) ) {
	/**
	 * Renderiza o modelo ativo ('header' ou 'footer') do Elementor na área atual.
	 *
	 * @param string $template_type Tipo de modelo ('header' ou 'footer').
	 * @return bool True se o modelo foi encontrado e renderizado com conteúdo não vazio.
	 */
	function store_render_active_template( string $template_type ): bool {
		if ( ! class_exists( '\\Elementor\\Plugin' ) || ! isset( \Elementor\Plugin::$instance->frontend ) ) {
			return false;
		}

		$template_id = store_get_active_template_id( $template_type );
		if ( $template_id <= 0 || (int) get_queried_object_id() === $template_id ) {
			return false;
		}

		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );
		if ( empty( $content ) ) {
			return false;
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return true;
	}
}

/**
 * Enfileira a base do Elementor e o CSS/assets dos modelos de Header e Footer no head,
 * inclusive em rotas não construídas no Elementor (WooCommerce, busca, arquivo e páginas padrão).
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return;
		}

		$header_id = store_get_active_template_id( 'header' );
		$footer_id = store_get_active_template_id( 'footer' );
		$tpl_ids   = array_values( array_filter( array( $header_id, $footer_id ) ) );

		if ( empty( $tpl_ids ) ) {
			return;
		}

		if ( ! wp_style_is( 'elementor-frontend', 'enqueued' ) ) {
			do_action( 'elementor/frontend/before_enqueue_styles' );

			if ( ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_font_icon_svg' ) || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				wp_enqueue_style( 'elementor-icons' );
			}

			wp_enqueue_style( 'elementor-frontend' );

			if ( is_admin_bar_showing() ) {
				wp_enqueue_style( 'elementor-wp-admin-bar' );
			}

			do_action( 'elementor/frontend/after_enqueue_styles' );
		}

		foreach ( $tpl_ids as $tpl_id ) {
			if ( class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
				\Elementor\Core\Files\CSS\Post::create( $tpl_id )->enqueue();
			}

			$page_assets = get_post_meta( $tpl_id, '_elementor_page_assets', true );
			if ( is_array( $page_assets ) ) {
				if ( ! empty( $page_assets ) && isset( \Elementor\Plugin::$instance->assets_loader ) ) {
					\Elementor\Plugin::$instance->assets_loader->enable_assets( $page_assets );
				}
			} elseif ( isset( \Elementor\Plugin::$instance->documents ) ) {
				$tpl_doc = \Elementor\Plugin::$instance->documents->get( $tpl_id );
				if ( $tpl_doc ) {
					$tpl_doc->update_runtime_elements();
				}
			}
		}
	},
	25
);

/**
 * Renderiza o Header em páginas com template Elementor Canvas
 * quando houver modelo atribuído para a página atual.
 */
add_action(
	'elementor/page_templates/canvas/before_content',
	function () {
		store_render_active_template( 'header' );
	}
);

/**
 * Renderiza o Footer em páginas com template Elementor Canvas
 * quando houver modelo atribuído para a página atual.
 */
add_action(
	'elementor/page_templates/canvas/after_content',
	function () {
		store_render_active_template( 'footer' );
	}
);

if ( ! function_exists( 'store_is_pix_gateway' ) ) {
	/**
	 * Verifica se o gateway de pagamento informado corresponde a um método PIX configurado.
	 *
	 * @param string $gateway_id ID do gateway WooCommerce.
	 * @return bool
	 */
	function store_is_pix_gateway( string $gateway_id ): bool {
		$gateway_id = strtolower( trim( $gateway_id ) );
		if ( '' === $gateway_id ) {
			return false;
		}
		$configured = array_map( 'strtolower', Store_Glossary::get_list( 'pix_gateway_ids' ) );
		return in_array( $gateway_id, $configured, true );
	}
}

if ( ! function_exists( 'store_resolve_chosen_payment_method' ) ) {
	/**
	 * Resolve o método de pagamento selecionado na requisição atual de checkout.
	 *
	 * @return string
	 */
	function store_resolve_chosen_payment_method(): string {
		if ( ! empty( $_POST['payment_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( ! empty( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted = array();
			parse_str( (string) wp_unslash( $_POST['post_data'] ), $posted ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $posted['payment_method'] ) ) {
				return sanitize_text_field( (string) $posted['payment_method'] );
			}
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$session_pm = (string) WC()->session->get( 'chosen_payment_method' );
			if ( '' !== $session_pm ) {
				return $session_pm;
			}
		}

		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			$available = WC()->payment_gateways()->get_available_payment_gateways();
			if ( ! empty( $available ) ) {
				return (string) array_key_first( $available );
			}
		}

		return '';
	}
}

/**
 * Aplica desconto dinâmico de pagamento à vista/PIX durante o Checkout
 * quando o gateway escolhido pertencer à lista configurada em `pix_gateway_ids`.
 */
add_action(
	'woocommerce_cart_calculate_fees',
	function ( WC_Cart $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$wc_ajax         = isset( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_checkout_ctx = ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() )
			|| in_array( $wc_ajax, array( 'update_order_review', 'checkout' ), true );

		if ( ! $is_checkout_ctx ) {
			return;
		}

		$discount_pct = Store_Glossary::get_float( 'cash_discount_percent' );
		if ( $discount_pct <= 0 ) {
			return;
		}

		$chosen_pm = store_resolve_chosen_payment_method();
		if ( ! store_is_pix_gateway( $chosen_pm ) ) {
			return;
		}

		$base = max( 0.0, (float) $cart->get_subtotal() - (float) $cart->get_discount_total() );
		if ( $base <= 0 ) {
			return;
		}

		$discount_amount = round( $base * ( $discount_pct / 100 ), 2 );
		if ( $discount_amount <= 0 ) {
			return;
		}

		$pct_text = rtrim( rtrim( number_format( $discount_pct, 2, ',', '' ), '0' ), ',' );
		$cart->add_fee( sprintf( 'Desconto à vista (%s%% PIX)', $pct_text ), -$discount_amount, false );
	},
	20
);

/**
 * Redireciona para o Checkout imediato quando o botão "Comprar Agora" estiver marcado.
 */
add_filter(
	'woocommerce_add_to_cart_redirect',
	function ( string $url ): string {
		if ( ! empty( $_REQUEST['store_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return wc_get_checkout_url();
		}
		return $url;
	},
	20
);

/**
 * Mantém o contador do ícone de Carrinho no cabeçalho sincronizado via AJAX WooCommerce.
 */
add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( array $fragments ): array {
		$count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
		$fragments['[data-store-cart-badge]'] = '<span class="ds-header-badge" data-store-cart-badge' . ( $count <= 0 ? ' hidden' : '' ) . '>' . esc_html( $count ) . '</span>';
		return $fragments;
	}
);

if ( ! function_exists( 'store_render_funnel_steps' ) ) {
	/**
	 * Renderiza a barra de etapas do funil (Carrinho -> Dados & Entrega -> Confirmação).
	 *
	 * @param int $active_step Etapa ativa (1, 2 ou 3).
	 * @return string
	 */
	function store_render_funnel_steps( int $active_step = 1 ): string {
		$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/carrinho/' );
		$chk_url  = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );

		$step_cls = static function ( int $step ) use ( $active_step ): string {
			if ( $step < $active_step ) {
				return 'ds-funnel-step ds-funnel-step--done';
			}
			if ( $step === $active_step ) {
				return 'ds-funnel-step ds-funnel-step--active';
			}
			return 'ds-funnel-step';
		};

		ob_start();
		?>
		<nav class="ds-funnel-steps no-print" aria-label="Etapas da compra">
			<a href="<?php echo esc_url( $cart_url ); ?>" class="<?php echo esc_attr( $step_cls( 1 ) ); ?>">
				<span>1.</span> Carrinho
			</a>
			<span class="ds-funnel-line" aria-hidden="true"></span>
			<a href="<?php echo esc_url( $chk_url ); ?>" class="<?php echo esc_attr( $step_cls( 2 ) ); ?>">
				<span>2.</span> Dados &amp; Entrega
			</a>
			<span class="ds-funnel-line" aria-hidden="true"></span>
			<span class="<?php echo esc_attr( $step_cls( 3 ) ); ?>">
				<span>3.</span> Confirmação
			</span>
		</nav>
		<?php
		return (string) ob_get_clean();
	}
}

add_shortcode(
	'store_order_confirmation',
	function (): string {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order = null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$req_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : ( isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$req_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( $req_id > 0 && '' !== $req_key ) {
			$candidate = wc_get_order( $req_id );
			if ( $candidate instanceof WC_Order && $candidate->key_is_valid( $req_key ) ) {
				$order = $candidate;
			}
		} elseif ( 0 === $req_id && is_user_logged_in() ) {
			$orders = wc_get_orders(
				array(
					'customer_id' => get_current_user_id(),
					'limit'       => 1,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
			if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
				$order = $orders[0];
			}
		}

		ob_start();
		if ( $order instanceof WC_Order ) {
			wc_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
		} else {
			$track_page = get_page_by_path( 'rastreio' );
			$track_url  = $track_page ? get_permalink( $track_page->ID ) : home_url( '/rastreio/' );
			$shop_url   = wc_get_page_permalink( 'shop' );
			?>
			<div class="ds-empty-box">
				<div class="ds-empty-box__icon" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
				</div>
				<h3>Acompanhe seu pedido</h3>
				<p>Caso tenha acabado de concluir uma compra ou queira consultar o código PIX e o rastreio, utilize a consulta de pedido informando o número e o e-mail de faturamento.</p>
				<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">
					<a href="<?php echo esc_url( $track_url ); ?>" class="ds-btn ds-btn-dark">Consultar Pedido</a>
					<a href="<?php echo esc_url( $shop_url ); ?>" class="ds-btn ds-btn-soft">Voltar à Loja</a>
				</div>
			</div>
			<?php
		}
		return (string) ob_get_clean();
	}
);

/**
 * Adiciona atalho de rastreamento às ações de pedido na área Minha Conta.
 */
add_filter(
	'woocommerce_my_account_my_orders_actions',
	function ( array $actions, WC_Order $order ): array {
		$track_page = get_page_by_path( 'rastreio' );
		if ( $track_page ) {
			$actions['track'] = array(
				'url'  => add_query_arg(
					array(
						'pedido' => $order->get_order_number(),
					),
					get_permalink( $track_page->ID )
				),
				'name' => 'Rastrear',
			);
		}
		return $actions;
	},
	10,
	2
);

/**
 * Remove widgets duplicados da calculadora de frete de terceiros na Página de Produto,
 * mantendo apenas o simulador unificado do tema sem afetar o cálculo de fretes no carrinho/checkout.
 */
add_action(
	'wp',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $wp_filter;
		if ( empty( $wp_filter ) || ! is_array( $wp_filter ) ) {
			return;
		}

		$blocked_classes = array(
			'MelhorEnvio\\Controllers\\ShowCalculatorProductPage',
			'MelhorEnvio\\Http\\Controllers\\Frontend\\ProductShippingCalculatorController',
		);

		foreach ( $wp_filter as $hook_name => $hook_obj ) {
			if ( 0 !== strpos( (string) $hook_name, 'woocommerce_' ) || ! $hook_obj instanceof WP_Hook ) {
				continue;
			}

			foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback_data ) {
					$fn = $callback_data['function'] ?? null;
					if ( is_array( $fn ) && isset( $fn[0] ) && is_object( $fn[0] ) && in_array( get_class( $fn[0] ), $blocked_classes, true ) ) {
						remove_action( $hook_name, $fn, (int) $priority );
					}
				}
			}
		}
	},
	999
);

/**
 * Localização PT-BR de disponibilidade de estoque em produtos.
 */
add_filter(
	'woocommerce_get_availability_text',
	function ( string $availability, $product ): string {
		if ( ! $product instanceof WC_Product ) {
			return $availability;
		}

		if ( ! $product->is_in_stock() ) {
			if ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
				return 'Disponível sob encomenda';
			}
			return 'Indisponível';
		}

		if ( $product->managing_stock() && $product->get_stock_quantity() > 0 ) {
			$stock      = $product->get_stock_quantity();
			$stock_fmt  = wc_format_stock_quantity_for_display( $stock, $product );
			$stock_mode = get_option( 'woocommerce_stock_format', '' );

			if ( 'low_amount' === $stock_mode && $stock <= wc_get_low_stock_amount( $product ) ) {
				return sprintf( 'Restam apenas %s unidades em estoque', $stock_fmt );
			}
			if ( 'no_amount' !== $stock_mode ) {
				return sprintf( '%s em estoque', $stock_fmt );
			}
		} elseif ( $product->backorders_allowed() && $product->backorders_require_notification() ) {
			return 'Disponível sob encomenda';
		}

		return 'Em estoque';
	},
	20,
	2
);

/**
 * Localização PT-BR da política de privacidade no Checkout e Minha Conta.
 */
add_filter(
	'woocommerce_get_privacy_policy_text',
	function ( string $text, string $type ): string {
		if ( 'checkout' === $type ) {
			return 'Seus dados pessoais serão utilizados para processar o seu pedido, emitir a documentação fiscal e aprimorar sua experiência nesta loja, conforme descrito em nossa [privacy_policy].';
		}
		if ( 'registration' === $type ) {
			return 'Seus dados pessoais serão utilizados para gerenciar o acesso à sua conta e melhorar sua experiência nesta loja, conforme descrito em nossa [privacy_policy].';
		}
		return $text;
	},
	20,
	2
);

/**
 * Localização PT-BR do breadcrumb WooCommerce.
 */
add_filter(
	'woocommerce_get_breadcrumb',
	function ( array $crumbs ): array {
		foreach ( $crumbs as $idx => $crumb ) {
			if ( empty( $crumb[0] ) ) {
				continue;
			}
			$label = (string) $crumb[0];
			if ( 0 === $idx && 'Home' === $label ) {
				$crumbs[ $idx ][0] = 'Início';
			} elseif ( preg_match( '/^Search results for\s+(?:&ldquo;|“)(.+?)(?:&rdquo;|”)$/u', $label, $m ) ) {
				$crumbs[ $idx ][0] = 'Resultados da busca: “' . $m[1] . '”';
			} elseif ( preg_match( '/^Page\s+(\d+)$/i', $label, $m ) ) {
				$crumbs[ $idx ][0] = 'Página ' . $m[1];
			}
		}
		return $crumbs;
	},
	20
);

/**
 * Localização PT-BR do botão principal do checkout, status e tabelas de conta/pedido.
 */
add_filter(
	'woocommerce_order_button_text',
	function (): string {
		return 'Finalizar Pedido';
	},
	20
);

add_filter(
	'wc_order_statuses',
	function ( array $statuses ): array {
		$pt_statuses = array(
			'wc-pending'    => 'Aguardando pagamento',
			'wc-processing' => 'Em processamento',
			'wc-on-hold'    => 'Aguardando confirmação',
			'wc-completed'  => 'Concluído',
			'wc-cancelled'  => 'Cancelado',
			'wc-refunded'   => 'Reembolsado',
			'wc-failed'     => 'Pagamento recusado',
		);
		foreach ( $pt_statuses as $key => $label ) {
			if ( isset( $statuses[ $key ] ) ) {
				$statuses[ $key ] = $label;
			}
		}
		return $statuses;
	},
	20
);

add_filter(
	'woocommerce_account_menu_items',
	function ( array $items ): array {
		$map = array(
			'dashboard'       => 'Painel',
			'orders'          => 'Pedidos',
			'downloads'       => 'Downloads',
			'edit-address'    => 'Endereços',
			'edit-account'    => 'Dados da Conta',
			'customer-logout' => 'Sair',
		);
		foreach ( $map as $endpoint => $label ) {
			if ( isset( $items[ $endpoint ] ) ) {
				$items[ $endpoint ] = $label;
			}
		}
		return $items;
	},
	20
);

add_filter(
	'woocommerce_account_orders_columns',
	function ( array $columns ): array {
		$map = array(
			'order-number'  => 'Pedido',
			'order-date'    => 'Data',
			'order-status'  => 'Status',
			'order-total'   => 'Total',
			'order-actions' => 'Ações',
		);
		foreach ( $map as $key => $label ) {
			if ( isset( $columns[ $key ] ) ) {
				$columns[ $key ] = $label;
			}
		}
		return $columns;
	},
	20
);

add_filter(
	'woocommerce_get_order_item_totals',
	function ( array $total_rows ): array {
		$label_map = array(
			'cart_subtotal'  => 'Subtotal:',
			'discount'       => 'Desconto:',
			'shipping'       => 'Entrega:',
			'payment_method' => 'Pagamento:',
			'order_total'    => 'Total:',
		);
		foreach ( $label_map as $key => $label ) {
			if ( isset( $total_rows[ $key ]['label'] ) ) {
				$total_rows[ $key ]['label'] = $label;
			}
		}
		return $total_rows;
	},
	20
);

/**
 * Fallback PT-BR para strings de interface do WooCommerce no front-end quando o pacote .mo não estiver carregado.
 */
add_filter(
	'gettext',
	function ( string $translation, string $text, string $domain ): string {
		if ( 'woocommerce' !== $domain || $translation !== $text || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $translation;
		}

		static $map = array(
			'Place order'                                                              => 'Finalizar Pedido',
			'Proceed to checkout'                                                      => 'Finalizar Compra',
			'Update cart'                                                              => 'Atualizar carrinho',
			'Apply coupon'                                                             => 'Aplicar cupom',
			'Have a coupon?'                                                           => 'Possui um cupom de desconto?',
			'Click here to enter your code'                                            => 'Clique aqui para informar seu código',
			'If you have a coupon code, please apply it below.'                        => 'Se você possui um cupom de desconto, informe o código abaixo.',
			'Coupon code'                                                              => 'Código do cupom',
			'Returning customer?'                                                      => 'Já é cliente?',
			'Click here to login'                                                      => 'Clique aqui para entrar',
			'Ship to a different address?'                                             => 'Entregar em um endereço diferente?',
			'Additional information'                                                   => 'Informações adicionais',
			'Order notes'                                                              => 'Observações do pedido',
			'Notes about your order, e.g. special notes for delivery.'                 => 'Observações sobre o pedido ou entrega (opcional).',
			'Product'                                                                  => 'Produto',
			'Quantity'                                                                 => 'Quantidade',
			'Price'                                                                    => 'Preço',
			'Shipping'                                                                 => 'Entrega',
			'Free!'                                                                    => 'Grátis',
			'Free shipping'                                                            => 'Frete grátis',
			'Flat rate'                                                                => 'Frete fixo',
			'Local pickup'                                                             => 'Retirada na loja',
			'Direct bank transfer'                                                     => 'Transferência Bancária / PIX',
			'Cash on delivery'                                                         => 'Pagamento na Entrega',
			'View cart'                                                                => 'Ver carrinho',
			'Continue shopping'                                                        => 'Continuar comprando',
			'Choose an option'                                                         => 'Escolha uma opção',
			'Clear'                                                                    => 'Limpar',
			'Reset'                                                                    => 'Limpar',
			'Sorry, no products matched your selection. Please choose a different combination.' => 'Esta combinação não está disponível. Escolha outra opção.',
			'Sorry, this product is unavailable. Please choose a different combination.'        => 'Este produto está temporariamente indisponível.',
			'Please read and accept the terms and conditions to proceed with your order.'       => 'Leia e aceite os termos e condições para finalizar o pedido.',
			'I have read and agree to the website %s'                                  => 'Li e concordo com os %s da loja',
			'terms and conditions'                                                     => 'termos e condições',
			'privacy policy'                                                           => 'política de privacidade',
			'Coupon code applied successfully.'                                        => 'Cupom aplicado com sucesso.',
			'Coupon code removed successfully.'                                        => 'Cupom removido com sucesso.',
			'Cart updated.'                                                            => 'Carrinho atualizado.',
			'[Remove]'                                                                 => '[Remover]',
			'Remove'                                                                   => 'Remover',
			'Coupon: %s'                                                               => 'Cupom: %s',
			'%s is a required field.'                                                  => '%s é um campo obrigatório.',
			'Billing %s'                                                               => 'Faturamento: %s',
			'Shipping %s'                                                              => 'Entrega: %s',
			'View'                                                                     => 'Ver',
			'Pay'                                                                      => 'Pagar',
			'Cancel'                                                                   => 'Cancelar',
			'Undo?'                                                                    => 'Desfazer?',
			'&ldquo;%s&rdquo; has been added to your cart.'                            => '“%s” foi adicionado ao seu carrinho.',
			'%s has been added to your cart.'                                          => '%s foi adicionado ao seu carrinho.',
			'&ldquo;%s&rdquo; removed.'                                                => '“%s” removido do carrinho.',
			'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.' => 'Esqueceu sua senha? Informe seu nome de usuário ou e-mail para receber o link de redefinição.',
			'Reset password'                                                           => 'Redefinir senha',
			'Username or email'                                                        => 'Usuário ou e-mail',
			'Billing address'                                                          => 'Endereço de Cobrança',
			'Shipping address'                                                         => 'Endereço de Entrega',
			'Edit Billing address'                                                     => 'Editar endereço',
			'Edit Shipping address'                                                    => 'Editar endereço',
			'You have not set up this type of address yet.'                            => 'Você ainda não cadastrou este endereço.',
			'The following addresses will be used on the checkout page by default.'    => 'Os endereços abaixo serão utilizados como padrão durante a finalização da compra.',
		);

		return $map[ $text ] ?? $translation;
	},
	20,
	3
);

/**
 * Traduz textos de disponibilidade de estoque na página de produto (simples e variações).
 */
add_filter(
	'woocommerce_get_availability_text',
	function ( string $availability, WC_Product $product ): string {
		if ( ! $product->is_in_stock() ) {
			return 'Indisponível no momento';
		}

		$qty = $product->get_stock_quantity();
		if ( $product->managing_stock() && null !== $qty ) {
			$qty_int = (int) $qty;
			if ( $qty_int > 0 ) {
				return 1 === $qty_int ? '1 unidade em estoque' : sprintf( '%d unidades em estoque', $qty_int );
			}
			if ( $product->backorders_allowed() ) {
				return 'Disponível sob encomenda';
			}
		}

		if ( $product->is_on_backorder( 1 ) ) {
			return 'Disponível sob encomenda';
		}

		return 'Disponível em estoque';
	},
	10,
	2
);

/**
 * Remove o simulador de frete nativo do plugin Melhor Envio na página de produto
 * para evitar duplicação com o módulo canônico de cotação CEP do tema.
 */
add_action(
	'wp',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $wp_filter;
		if ( ! is_array( $wp_filter ) ) {
			return;
		}

		$target_classes = array(
			'MelhorEnvio\\Controllers\\ShowCalculatorProductPage',
			'MelhorEnvio\\Http\\Controllers\\Frontend\\ProductShippingCalculatorController',
		);

		foreach ( $wp_filter as $hook_name => $hook_obj ) {
			if ( 0 !== strpos( (string) $hook_name, 'woocommerce_' ) || ! $hook_obj instanceof WP_Hook ) {
				continue;
			}
			foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $cb ) {
					if ( ! is_array( $cb['function'] ) || ! is_object( $cb['function'][0] ) ) {
						continue;
					}
					$cls = get_class( $cb['function'][0] );
					if ( in_array( $cls, $target_classes, true ) ) {
						remove_action( $hook_name, $cb['function'], (int) $priority );
					}
				}
			}
		}
	},
	20
);

/**
 * Polyfill de segurança para crypto.randomUUID em ambientes locais sem SSL (HTTP).
 * Previne falhas de inicialização em SDKs de pagamento (ex: Mercado Pago).
 */
add_action(
	'wp_head',
	function () {
		?>
		<script>
		if (!window.crypto || !window.crypto.randomUUID) {
			window.crypto = window.crypto || {};
			window.crypto.randomUUID = function() {
				return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, function(c) {
					return (c ^ (crypto.getRandomValues ? crypto.getRandomValues(new Uint8Array(1))[0] : Math.floor(Math.random() * 16)) & 15 >> (c / 4)).toString(16);
				});
			};
		}
		</script>
		<?php
	},
	1
);

/**
 * Desacopla o interceptor de cotação do Melhor Envio no checkout caso o token da API
 * não esteja configurado no ambiente, prevenindo respostas 401 que abortam a criação do pedido.
 */
add_action(
	'wp_loaded',
	function () {
		if ( class_exists( 'MelhorEnvio\\Services\\TokenService' ) ) {
			$token_service = new \MelhorEnvio\Services\TokenService();
			if ( ! $token_service->get() ) {
				global $wp_filter;
				if ( isset( $wp_filter['woocommerce_checkout_order_processed'] ) && $wp_filter['woocommerce_checkout_order_processed'] instanceof WP_Hook ) {
					foreach ( $wp_filter['woocommerce_checkout_order_processed']->callbacks as $priority => $callbacks ) {
						foreach ( $callbacks as $cb ) {
							if ( is_array( $cb['function'] ) && is_object( $cb['function'][0] ) && is_a( $cb['function'][0], 'MelhorEnvio\\Controllers\\QuotationController' ) ) {
								remove_action( 'woocommerce_checkout_order_processed', $cb['function'], (int) $priority );
							}
						}
					}
				}
			}
		}
	},
	20
);

