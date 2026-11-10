<?php
/**
 * Painel Inicial (Dashboard) de Minha Conta com KPIs reais e pedido mais recente.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$customer_id = get_current_user_id();
$orders      = wc_get_orders(
	array(
		'customer_id' => $customer_id,
		'limit'       => 20,
		'orderby'     => 'date',
		'order'       => 'DESC',
	)
);
$total_orders   = is_array( $orders ) ? count( $orders ) : 0;
$latest_order   = ! empty( $orders[0] ) && $orders[0] instanceof WC_Order ? $orders[0] : null;
$wishlist_count = function_exists( 'store_get_wishlist_count' ) ? store_get_wishlist_count() : 0;
$wishlist_page  = get_page_by_path( 'favoritos' );
$wishlist_url   = $wishlist_page ? get_permalink( $wishlist_page->ID ) : '';
$track_page     = get_page_by_path( 'rastreio' );

$customer_obj = function_exists( 'WC' ) && WC()->customer ? WC()->customer : new WC_Customer( $customer_id );
$bill_city    = trim( (string) $customer_obj->get_billing_city() );
$bill_state   = trim( (string) $customer_obj->get_billing_state() );
$bill_cep     = trim( (string) $customer_obj->get_billing_postcode() );
$city_summary = $bill_city ? trim( $bill_city . ( $bill_state ? ' / ' . $bill_state : '' ) ) : 'Não cadastrado';

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<div class="ds-kpi-grid-3">
	<div class="ds-kpi-card">
		<span class="ds-kpi-card__label">Pedidos Realizados</span>
		<strong class="ds-kpi-card__value"><?php echo esc_html( $total_orders ); ?></strong>
		<span class="ds-profile-meta">
			<?php
			if ( $latest_order ) {
				echo esc_html( 'Último: #' . $latest_order->get_order_number() . ' (' . wc_get_order_status_name( $latest_order->get_status() ) . ')' );
			} else {
				echo 'Nenhum pedido realizado ainda';
			}
			?>
		</span>
	</div>

	<div class="ds-kpi-card">
		<span class="ds-kpi-card__label">Endereço Principal</span>
		<strong class="ds-kpi-card__value" style="font-size:1.05rem;"><?php echo esc_html( $city_summary ); ?></strong>
		<span class="ds-profile-meta">
			<?php echo $bill_cep ? esc_html( 'CEP ' . $bill_cep ) : '<a href="' . esc_url( wc_get_endpoint_url( 'edit-address' ) ) . '" class="ds-link-accent">Cadastrar endereço</a>'; ?>
		</span>
	</div>

	<div class="ds-kpi-card">
		<span class="ds-kpi-card__label">Lista de Desejos</span>
		<strong class="ds-kpi-card__value"><?php echo esc_html( $wishlist_count ); ?></strong>
		<span class="ds-profile-meta">
			<?php if ( '' !== $wishlist_url ) : ?>
				<a href="<?php echo esc_url( $wishlist_url ); ?>" class="ds-link-accent">Ver produtos salvos &rarr;</a>
			<?php else : ?>
				Itens salvos para compra futura
			<?php endif; ?>
		</span>
	</div>
</div>

<?php if ( $latest_order instanceof WC_Order ) : ?>
	<?php
	$latest_num   = $latest_order->get_order_number();
	$latest_date  = $latest_order->get_date_created() ? wc_format_datetime( $latest_order->get_date_created(), 'd/m/Y' ) : '';
	$latest_track = $track_page
		? add_query_arg(
			array(
				'pedido' => $latest_num,
			),
			get_permalink( $track_page->ID )
		)
		: '';
	?>
	<div class="ds-panel-card" style="margin-bottom:20px;">
		<div class="ds-card-head">
			<h2 class="ds-card-head__title">Pedido Mais Recente</h2>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'orders' ) ); ?>" class="ds-link-accent">Ver todos os pedidos &rarr;</a>
		</div>

		<div class="ds-recent-order-row">
			<div>
				<strong style="font-size:14px;color:var(--ds-zinc-900);">
					Pedido #<?php echo esc_html( $latest_num ); ?>
				</strong>
				<span class="ds-pill-badge ds-pill-badge--amber-soft" style="margin-left:8px;">
					<?php echo esc_html( wc_get_order_status_name( $latest_order->get_status() ) ); ?>
				</span>
				<p class="ds-profile-meta" style="margin-top:4px;">
					Em <?php echo esc_html( $latest_date ); ?> &bull; Total: <strong><?php echo wp_kses_post( $latest_order->get_formatted_order_total() ); ?></strong>
				</p>
			</div>

			<div class="ds-order-actions-cell">
				<?php if ( $latest_order->needs_payment() ) : ?>
					<a href="<?php echo esc_url( $latest_order->get_checkout_payment_url() ); ?>" class="ds-btn ds-btn-amber ds-btn-sm">Pagar Agora</a>
				<?php endif; ?>
				<?php if ( '' !== $latest_track ) : ?>
					<a href="<?php echo esc_url( $latest_track ); ?>" class="ds-btn ds-btn-soft ds-btn-sm">Rastrear</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( $latest_order->get_view_order_url() ); ?>" class="ds-btn ds-btn-dark ds-btn-sm">Detalhes</a>
			</div>
		</div>
	</div>
<?php endif; ?>

<div class="ds-panel-card">
	<p style="margin-top:0;font-size:13px;color:var(--ds-slate-600);">
		<?php
		printf(
			wp_kses( 'Olá %1$s (não é %1$s? <a href="%2$s">Sair</a>).', $allowed_html ),
			'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
			esc_url( wc_logout_url() )
		);
		?>
	</p>
	<p style="margin-bottom:0;font-size:13px;color:var(--ds-slate-600);line-height:1.6;">
		<?php
		$dashboard_desc = wc_shipping_enabled()
			? 'A partir do seu painel você pode acompanhar seus <a href="%1$s">pedidos recentes</a>, gerenciar seus <a href="%2$s">endereços de cobrança e entrega</a> e <a href="%3$s">editar sua senha e dados cadastrais</a>.'
			: 'A partir do seu painel você pode acompanhar seus <a href="%1$s">pedidos recentes</a>, gerenciar seu <a href="%2$s">endereço de cobrança</a> e <a href="%3$s">editar sua senha e dados cadastrais</a>.';
		printf(
			wp_kses( $dashboard_desc, $allowed_html ),
			esc_url( wc_get_endpoint_url( 'orders' ) ),
			esc_url( wc_get_endpoint_url( 'edit-address' ) ),
			esc_url( wc_get_endpoint_url( 'edit-account' ) )
		);
		?>
	</p>
</div>

<?php
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
