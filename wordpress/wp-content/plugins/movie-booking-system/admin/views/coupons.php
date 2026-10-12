<?php
/**
 * Coupons Management View
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'booking_coupons';
$coupons = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
?>
<div class="wrap mbs-admin-wrap">
	<h1><i class="fa-solid fa-tags" style="color: #4f46e5; margin-right: 8px;"></i> Quản Lý Mã Giảm Giá / Khuyến Mãi</h1>

	<div class="mbs-coupons-grid">
		<!-- Left: Form to Add/Edit Coupon -->
		<div class="mbs-card-box">
			<h3>Tạo Mã Khuyến Mãi Mới</h3>
			<form id="mbs-coupon-form" onsubmit="mbsSaveCouponAdmin(event)">
				<div class="mbs-form-group">
					<label>Mã Giảm Giá (Code) <span class="req">*</span></label>
					<input type="text" id="mbs-cp-code" placeholder="VD: SIEUSALE50" required style="text-transform: uppercase;" />
				</div>

				<div class="mbs-form-row">
					<div class="mbs-form-group">
						<label>Loại Giảm</label>
						<select id="mbs-cp-type">
							<option value="percentage">Phần trăm (%)</option>
							<option value="fixed">Số tiền cố định (VNĐ)</option>
						</select>
					</div>
					<div class="mbs-form-group">
						<label>Giá Trị Giảm <span class="req">*</span></label>
						<input type="number" step="any" id="mbs-cp-value" placeholder="VD: 20 hoặc 50000" required />
					</div>
				</div>

				<div class="mbs-form-row">
					<div class="mbs-form-group">
						<label>Đơn Tối Thiểu (VNĐ)</label>
						<input type="number" id="mbs-cp-min" value="0" />
					</div>
					<div class="mbs-form-group">
						<label>Giới Hạn Lượt Dùng</label>
						<input type="number" id="mbs-cp-limit" value="100" />
					</div>
				</div>

				<div class="mbs-form-group">
					<label>Ngày Hết Hạn</label>
					<input type="date" id="mbs-cp-expiry" />
				</div>

				<button type="submit" class="button button-primary button-large" id="mbs-save-cp-btn">
					💾 Thêm / Cập Nhật Mã Giảm Giá
				</button>
			</form>
		</div>

		<!-- Right: Existing Coupons Table -->
		<div class="mbs-card-box">
			<h3>Danh Sách Mã Đang Hoạt Động</h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Mã Code</th>
						<th>Mức Giảm</th>
						<th>Đơn Tối Thiểu</th>
						<th>Đã Dùng / Giới Hạn</th>
						<th>Hết Hạn</th>
						<th>Thao Tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $coupons ) ) : ?>
						<tr><td colspan="6">Chưa có mã giảm giá nào.</td></tr>
					<?php else : ?>
						<?php foreach ( $coupons as $c ) : ?>
							<tr>
								<td><strong class="mbs-coupon-pill"><?php echo esc_html( $c->code ); ?></strong></td>
								<td>
									<?php echo ( $c->discount_type === 'percentage' ) ? esc_html( $c->discount_value ) . '%' : number_format( $c->discount_value, 0, ',', '.' ) . 'đ'; ?>
								</td>
								<td><?php echo number_format( $c->min_order_amount, 0, ',', '.' ); ?>đ</td>
								<td><strong><?php echo esc_html( $c->used_count ); ?></strong> / <?php echo esc_html( $c->usage_limit ); ?></td>
								<td><?php echo $c->expiry_date ? esc_html( date( 'd/m/Y', strtotime( $c->expiry_date ) ) ) : 'Vô thời hạn'; ?></td>
								<td>
									<button type="button" class="button button-link-delete" onclick="mbsDeleteCoupon(<?php echo esc_attr( $c->id ); ?>)">
										Xóa
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
