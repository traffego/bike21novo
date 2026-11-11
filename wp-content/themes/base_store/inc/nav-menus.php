<?php
/**
 * Suporte a Menus de Navegação Nativos do WordPress e Shortcode Agnóstico.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna markup vetorial SVG para ícones de navegação de forma agnóstica.
 *
 * @param string $name Identificador do ícone.
 * @return string
 */
function store_get_nav_icon( string $name ): string {
	$icon = '';
	switch ( $name ) {
		case 'bicycle':
		case 'bike':
			$icon = '<svg class="ds-nav-icon ds-nav-icon--bike" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>';
			break;

		case 'ruler':
		case 'measure':
		case 'size-guide':
			$icon = '<svg class="ds-nav-icon ds-nav-icon--ruler" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21.3 8.7 8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4Z"/><path d="m14.5 3.5 2 2"/><path d="m11.5 6.5 2 2"/><path d="m8.5 9.5 2 2"/><path d="m5.5 12.5 2 2"/></svg>';
			break;
	}

	return apply_filters( 'store_nav_menu_icon', $icon, $name );
}

/**
 * Registra as localizações oficiais de menus do tema.
 */
add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			array(
				'primary_header'     => __( 'Menu Principal do Cabeçalho', 'base_store' ),
				'footer_departments' => __( 'Rodapé - Departamentos', 'base_store' ),
				'footer_support'     => __( 'Rodapé - Ajuda & Suporte', 'base_store' ),
			)
		);
	}
);

if ( ! class_exists( 'Store_Design_System_Nav_Walker' ) ) {
	/**
	 * Walker customizado para injetar classes canônicas do Design System nos itens de menu.
	 */
	class Store_Design_System_Nav_Walker extends Walker_Nav_Menu {
		/**
		 * Localização ou contexto do menu para prefixação de modificadores.
		 *
		 * @var string
		 */
		protected $location_slug = 'default';

		/**
		 * Construtor.
		 *
		 * @param string $location_slug Slug da localização.
		 */
		public function __construct( string $location_slug = 'default' ) {
			$this->location_slug = sanitize_html_class( $location_slug );
		}

		/**
		 * Inicia o elemento de nível do menu (LI).
		 *
		 * @param string   $output Passado por referência.
		 * @param WP_Post  $item   Objeto do item de menu.
		 * @param int      $depth  Profundidade.
		 * @param stdClass $args   Argumentos de wp_nav_menu.
		 * @param int      $id     ID atual.
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$is_active = in_array( 'current-menu-item', $classes, true ) ||
						in_array( 'current-menu-parent', $classes, true ) ||
						in_array( 'current-menu-ancestor', $classes, true );

			$item_classes = array(
				'ds-nav-item',
				'ds-nav-item--' . $this->location_slug,
			);
			if ( $is_active ) {
				$item_classes[] = 'is-active';
			}

			// Modificadores de estilo (Dark Pill / Highlight Âmbar)
			$has_pill_dark      = in_array( 'ds-nav-pill-dark', $classes, true ) || in_array( 'is-pill-dark', $classes, true );
			$has_pill_highlight = in_array( 'ds-nav-pill-highlight', $classes, true ) || in_array( 'is-pill-highlight', $classes, true );

			// Detecção automática agnóstica para rotas institucionais e departamento principal caso classes não tenham sido salvas
			if ( ! $has_pill_dark && ! $has_pill_highlight && 'primary_header' === $this->location_slug ) {
				if ( false !== stripos( (string) $item->url, 'guia-de-tamanhos' ) ) {
					$has_pill_highlight = true;
				} elseif ( 0 === $depth && 0 === ( (int) $item->menu_order - 1 ) && 'product_cat' === $item->object ) {
					$has_pill_dark = true;
				}
			}

			if ( $has_pill_dark ) {
				$item_classes[] = 'ds-nav-item--dark';
				$item_classes[] = 'ds-nav-pill-dark';
			}
			if ( $has_pill_highlight ) {
				$item_classes[] = 'ds-nav-item--highlight';
				$item_classes[] = 'ds-nav-pill-highlight';
			}

			$class_names = implode( ' ', array_filter( array_unique( $item_classes ) ) );
			$output     .= '<li class="' . esc_attr( $class_names ) . '">';

			$atts           = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target ) ? $item->target : '';
			if ( '_blank' === $item->target && empty( $item->xfn ) ) {
				$atts['rel'] = 'noopener noreferrer';
			} else {
				$atts['rel'] = $item->xfn;
			}
			$atts['href']         = ! empty( $item->url ) ? $item->url : '';
			$atts['aria-current'] = $is_active ? 'page' : '';

			$link_classes = array(
				'ds-nav-link',
				'ds-nav-link--' . $this->location_slug,
			);
			if ( $is_active ) {
				$link_classes[] = 'is-active';
			}
			if ( $has_pill_dark ) {
				$link_classes[] = 'ds-nav-link--dark';
				$link_classes[] = 'ds-nav-pill-dark';
			}
			if ( $has_pill_highlight ) {
				$link_classes[] = 'ds-nav-link--highlight';
				$link_classes[] = 'ds-nav-pill-highlight';
			}
			$atts['class'] = implode( ' ', array_filter( array_unique( $link_classes ) ) );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
					$value       = ( 'href' === $attr ) ? esc_url( (string) $value ) : esc_attr( (string) $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			// Resolução de Ícone
			$icon_html = '';
			foreach ( $classes as $cls ) {
				if ( 0 === strpos( $cls, 'has-icon-' ) ) {
					$icon_name = substr( $cls, 9 );
					$icon_html = store_get_nav_icon( $icon_name );
					if ( $icon_html ) {
						break;
					}
				}
			}

			if ( empty( $icon_html ) && 'primary_header' === $this->location_slug ) {
				if ( $has_pill_highlight || false !== stripos( (string) $item->url, 'guia-de-tamanhos' ) ) {
					$icon_html = store_get_nav_icon( 'ruler' );
				} elseif ( $has_pill_dark ) {
					$icon_html = store_get_nav_icon( 'bicycle' );
				}
			}

			$icon_html = apply_filters( 'store_nav_menu_item_icon_html', $icon_html, $item, $this->location_slug );

			$title = apply_filters( 'the_title', $item->title, $item->ID );
			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

			$item_output  = ( isset( $args->before ) ? $args->before : '' );
			$item_output .= '<a' . $attributes . '>';
			$item_output .= ( isset( $args->link_before ) ? $args->link_before : '' );
			if ( ! empty( $icon_html ) ) {
				$item_output .= $icon_html . ' ';
			}
			$item_output .= '<span class="ds-nav-text">' . $title . '</span>';
			$item_output .= ( isset( $args->link_after ) ? $args->link_after : '' );
			$item_output .= '</a>';
			$item_output .= ( isset( $args->after ) ? $args->after : '' );

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}
}

/**
 * Shortcode unificado [store_nav_menu] para inserção de menus do WordPress no Elementor.
 *
 * Exemplos de uso:
 * [store_nav_menu location="primary_header"]
 * [store_nav_menu location="footer_departments"]
 * [store_nav_menu location="footer_support"]
 *
 * @param array $atts Atributos do shortcode.
 * @return string
 */
function store_nav_menu_shortcode( $atts ): string {
	$a = shortcode_atts(
		array(
			'location'   => '',
			'menu'       => '',
			'class'      => '',
			'aria_label' => '',
		),
		$atts,
		'store_nav_menu'
	);

	$location   = sanitize_key( $a['location'] );
	$menu_param = sanitize_text_field( $a['menu'] );
	$extra_cls  = sanitize_html_class( $a['class'] );
	$aria_label = sanitize_text_field( $a['aria_label'] );

	$locations = get_nav_menu_locations();
	$has_menu  = false;

	if ( '' !== $location && ! empty( $locations[ $location ] ) ) {
		$has_menu = true;
	} elseif ( '' !== $menu_param ) {
		$has_menu = (bool) wp_get_nav_menu_object( $menu_param );
	}

	if ( ! $has_menu && current_user_can( 'edit_theme_options' ) ) {
		return sprintf(
			'<div class="ds-nav-empty-notice" style="font-size:12px;color:var(--ds-slate-400);padding:8px 0;">%s</div>',
			esc_html__( 'Nenhum menu atribuído à localização: ', 'base_store' ) . esc_html( $location ?: $menu_param )
		);
	}

	if ( ! $has_menu ) {
		return '';
	}

	$slug = $location ?: ( '' !== $menu_param ? sanitize_title( $menu_param ) : 'main' );

	$nav_classes = array(
		'ds-nav-wrap',
		'ds-nav-wrap--' . $slug,
	);
	if ( $extra_cls ) {
		$nav_classes[] = $extra_cls;
	}

	$list_classes = array(
		'ds-nav-list',
		'ds-nav-list--' . $slug,
	);

	if ( empty( $aria_label ) ) {
		$aria_label = 'primary_header' === $location ? __( 'Menu Principal de Categorias', 'base_store' ) : __( 'Navegação de Rodapé', 'base_store' );
	}

	$args = array(
		'echo'            => false,
		'container'       => 'nav',
		'container_class' => implode( ' ', $nav_classes ),
		'container_aria_label' => $aria_label,
		'menu_class'      => implode( ' ', $list_classes ),
		'fallback_cb'     => false,
		'depth'           => 1,
		'walker'          => new Store_Design_System_Nav_Walker( $slug ),
	);

	if ( '' !== $location ) {
		$args['theme_location'] = $location;
	}
	if ( '' !== $menu_param ) {
		$args['menu'] = $menu_param;
	}

	$output = wp_nav_menu( $args );
	return is_string( $output ) ? $output : '';
}
add_shortcode( 'store_nav_menu', 'store_nav_menu_shortcode' );
