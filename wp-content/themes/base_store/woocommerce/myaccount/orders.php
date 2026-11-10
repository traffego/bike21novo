<?php
/**
 * Histórico de Pedidos na área Minha Conta.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

$wp_button_class = isset( $wp_button_class ) ? $wp_button_class : ( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' );

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>

<?php if ( $has_orders ) : ?>

	<div class="ds-panel-card">
		<div class="ds-card-head">
			<h2 class="ds-card-head__title">Meus Pedidos</h2>
		</div>

		<div style="overflow-x:auto;">
			<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table ds-order-table">
				<thead>
					<tr>
						<?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
							<th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
						<?php endforeach; ?>
					</tr>
				</thead>

				<tbody>
					<?php
					foreach ( $customer_orders->orders as $customer_order ) {
						$order      = wc_get_order( $customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						if ( ! $order instanceof WC_Order ) {
							continue;
						}
						$item_count = $order->get_item_count() - $order->get_item_count_refunded();
						$st_slug    = $order->get_status();
						$st_badge   = 'ds-pill-badge--blue';
						if ( in_array( $st_slug, array( 'completed', 'processing' ), true ) ) {
							$st_badge = 'ds-pill-badge--emerald';
						} elseif ( in_array( $st_slug, array( 'pending', 'on-hold' ), true ) ) {
							$st_badge = 'ds-pill-badge--amber-soft';
						} elseif ( in_array( $st_slug, array( 'cancelled', 'failed', 'refunded' ), true ) ) {
							$st_badge = 'ds-pill-badge--rose';
						}
						?>
						<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $st_slug ); ?> order">
							<?php
							foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) :
								$is_order_number = 'order-number' === $column_id;
								?>
								<?php if ( $is_order_number ) : ?>
									<th class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>" scope="row">
								<?php else : ?>
									<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
								<?php endif; ?>

									<?php if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ) : ?>
										<?php do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); ?>

									<?php elseif ( $is_order_number ) : ?>
										<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" class="ds-link-accent" aria-label="<?php echo esc_attr( sprintf( 'Ver pedido #%s', $order->get_order_number() ) ); ?>">
											#<?php echo esc_html( $order->get_order_number() ); ?>
										</a>

									<?php elseif ( 'order-date' === $column_id ) : ?>
										<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m/Y' ) ); ?></time>

									<?php elseif ( 'order-status' === $column_id ) : ?>
										<span class="ds-pill-badge <?php echo esc_attr( $st_badge ); ?>">
											<?php echo esc_html( wc_get_order_status_name( $st_slug ) ); ?>
										</span>

									<?php elseif ( 'order-total' === $column_id ) : ?>
										<?php
										echo wp_kses_post( sprintf( '%1$s (%2$d %3$s)', $order->get_formatted_order_total(), $item_count, 1 === $item_count ? 'item' : 'itens' ) );
										?>

									<?php elseif ( 'order-actions' === $column_id ) : ?>
										<div class="ds-order-actions-cell">
											<?php
											$actions = wc_get_account_orders_actions( $order );
											if ( ! empty( $actions ) ) {
												foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
													$action_aria_label = ! empty( $action['aria-label'] )
														? $action['aria-label']
														: sprintf( '%1$s pedido #%2$s', $action['name'], $order->get_order_number() );
													$btn_variant       = 'view' === $key ? 'ds-btn-dark' : ( 'pay' === $key ? 'ds-btn-amber' : 'ds-btn-soft' );
													echo '<a href="' . esc_url( $action['url'] ) . '" class="ds-btn ds-btn-sm ' . esc_attr( $btn_variant ) . ' ' . sanitize_html_class( $key ) . '" aria-label="' . esc_attr( $action_aria_label ) . '">' . esc_html( $action['name'] ) . '</a>';
												}
											}
											?>
										</div>
									<?php endif; ?>

								<?php if ( $is_order_number ) : ?>
									</th>
								<?php else : ?>
									</td>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>

		<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

		<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
			<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination" style="margin-top:16px;display:flex;gap:10px;justify-content:flex-end;">
				<?php if ( 1 !== $current_page ) : ?>
					<a class="ds-btn ds-btn-soft ds-btn-sm" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">Anterior</a>
				<?php endif; ?>

				<?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
					<a class="ds-btn ds-btn-dark ds-btn-sm" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">Próxima</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

<?php else : ?>
	<div class="ds-empty-box">
		<div class="ds-empty-box__icon" aria-hidden="true">
			<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
		</div>
		<h3>Nenhum pedido realizado ainda</h3>
		<p>Quando você finalizar sua primeira compra na loja, poderá acompanhar todo o andamento e código de rastreio por aqui.</p>
		<a class="ds-btn ds-btn-dark<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			Explorar Produtos
		</a>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
