<?php
/**
 * Orders & Sold Tickets Management View
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_orders = $wpdb->prefix . 'booking_orders';

$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
$date_filter   = sanitize_text_field( $_GET['date'] ?? '' );
$search_query  = sanitize_text_field( $_GET['s'] ?? '' );

$where = array( '1=1' );
if ( ! empty( $status_filter ) ) {
	$where[] = $wpdb->prepare( "payment_status = %s", $status_filter );
}
if ( ! empty( $date_filter ) ) {
	$where[] = $wpdb->prepare( "DATE(created_at) = %s", $date_filter );
}
if ( ! empty( $search_query ) ) {
	$where[] = $wpdb->prepare( "(order_code LIKE %s OR customer_name LIKE %s OR customer_phone LIKE %s)", "%$search_query%", "%$search_query%", "%$search_query%" );
}

$where_sql = implode( ' AND ', $where );
$orders = $wpdb->get_results( "SELECT * FROM $table_orders WHERE $where_sql ORDER BY created_at DESC LIMIT 100" );
?>
<div class="wrap mbs-admin-wrap">
	<div class="mbs-header-row">
		<h1><i class="fa-solid fa-ticket" style="color: #4f46e5; margin-right: 8px;"></i> Quản Lý Đơn Hàng & Vé Đã Bán</h1>
		<div>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mbs-orders&mbs_export_orders=1' ), 'mbs_export_orders_nonce' ) ); ?>" class="button button-primary" style="display:inline-flex;align-items:center;gap:6px;">
				<i class="fa-solid fa-file-excel"></i> <span>Xuất Ra File Excel/CSV</span>
			</a>
		</div>
	</div>

	<!-- Filter Form -->
	<form method="get" class="mbs-filter-bar">
		<input type="hidden" name="page" value="mbs-orders" />
		<input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Tìm mã đơn, tên khách, SĐT..." />
		<select name="status">
			<option value="">-- Tất cả trạng thái --</option>
			<option value="paid" <?php selected( $status_filter, 'paid' ); ?>>Đã Thanh Toán (PAID)</option>
			<option value="pending" <?php selected( $status_filter, 'pending' ); ?>>Chờ Thanh Toán (PENDING)</option>
			<option value="cancelled" <?php selected( $status_filter, 'cancelled' ); ?>>Đã Hủy (CANCELLED)</option>
		</select>
		<input type="date" name="date" value="<?php echo esc_attr( $date_filter ); ?>" />
		<button type="submit" class="button">Lọc Dữ Liệu</button>
		<?php if ( $status_filter || $date_filter || $search_query ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbs-orders' ) ); ?>" class="button">Xóa Lọc</a>
		<?php endif; ?>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th width="140">Mã Đơn Hàng</th>
				<th>Khách Hàng</th>
				<th>Liên Hệ</th>
				<th>Tổng Tiền</th>
				<th>Giảm Giá</th>
				<th>Thực Thu</th>
				<th>Cổng TT</th>
				<th>Trạng Thái</th>
				<th>Ngày Đặt</th>
				<th width="100">Chi Tiết</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $orders ) ) : ?>
				<tr><td colspan="10">Không tìm thấy đơn hàng nào phù hợp với điều kiện tìm kiếm.</td></tr>
			<?php else : ?>
				<?php foreach ( $orders as $o ) : 
					$status_badge_class = ( $o->payment_status === 'paid' ) ? 'mbs-badge-success' : ( ( $o->payment_status === 'cancelled' ) ? 'mbs-badge-danger' : 'mbs-badge-warning' );
				?>
					<tr>
						<td><strong class="mbs-order-code"><?php echo esc_html( $o->order_code ); ?></strong></td>
						<td><strong><?php echo esc_html( $o->customer_name ); ?></strong></td>
						<td>
							<div>📞 <?php echo esc_html( $o->customer_phone ); ?></div>
							<small>✉️ <?php echo esc_html( $o->customer_email ); ?></small>
						</td>
						<td><?php echo esc_html( number_format( $o->total_amount, 0, ',', '.' ) . 'đ' ); ?></td>
						<td><?php echo esc_html( number_format( $o->discount_amount, 0, ',', '.' ) . 'đ' ); ?></td>
						<td><strong style="color: #16a34a;"><?php echo esc_html( number_format( $o->final_amount, 0, ',', '.' ) . 'đ' ); ?></strong></td>
						<td><span class="mbs-badge"><?php echo esc_html( strtoupper( $o->payment_method ) ); ?></span></td>
						<td><span class="mbs-badge <?php echo esc_attr( $status_badge_class ); ?>"><?php echo esc_html( strtoupper( $o->payment_status ) ); ?></span></td>
						<td><?php echo esc_html( date( 'd/m/Y H:i', strtotime( $o->created_at ) ) ); ?></td>
						<td>
							<button type="button" class="button button-small" onclick="mbsViewOrderDetail('<?php echo esc_attr( $o->order_code ); ?>')">
								Xem Vé
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Order Detail Modal -->
	<div id="mbs-admin-order-modal" class="mbs-admin-modal" style="display:none;">
		<div class="mbs-admin-modal-overlay" onclick="document.getElementById('mbs-admin-order-modal').style.display='none'"></div>
		<div class="mbs-admin-modal-card">
			<button class="mbs-modal-close-btn" onclick="document.getElementById('mbs-admin-order-modal').style.display='none'">×</button>
			<div id="mbs-admin-order-content">
				<p>Đang tải chi tiết vé...</p>
			</div>
		</div>
	</div>
</div>
