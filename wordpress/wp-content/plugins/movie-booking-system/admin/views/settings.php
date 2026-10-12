<?php
/**
 * Plugin Settings View
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['mbs_save_settings_nonce'] ) && wp_verify_nonce( $_POST['mbs_save_settings_nonce'], 'mbs_save_settings' ) ) {
	$new_settings = array(
		'seat_lock_duration'    => absint( $_POST['seat_lock_duration'] ?? 10 ),
		'cancel_allowed_before' => absint( $_POST['cancel_allowed_before'] ?? 120 ),
		'currency_symbol'       => sanitize_text_field( $_POST['currency_symbol'] ?? 'đ' ),
		'points_rate'           => absint( $_POST['points_rate'] ?? 10 ),
		'vnpay_sandbox'         => isset( $_POST['vnpay_sandbox'] ) ? 1 : 0,
		'vnpay_tmn_code'        => sanitize_text_field( $_POST['vnpay_tmn_code'] ?? '' ),
		'vnpay_hash_secret'     => sanitize_text_field( $_POST['vnpay_hash_secret'] ?? '' ),
		'vnpay_url'             => esc_url_raw( $_POST['vnpay_url'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html' ),
		'momo_sandbox'          => isset( $_POST['momo_sandbox'] ) ? 1 : 0,
		'momo_partner_code'     => sanitize_text_field( $_POST['momo_partner_code'] ?? '' ),
		'momo_access_key'       => sanitize_text_field( $_POST['momo_access_key'] ?? '' ),
		'momo_secret_key'       => sanitize_text_field( $_POST['momo_secret_key'] ?? '' ),
		'sms_api_key'           => sanitize_text_field( $_POST['sms_api_key'] ?? '' ),
		'email_sender_name'     => sanitize_text_field( $_POST['email_sender_name'] ?? get_bloginfo( 'name' ) ),
		'email_sender_address'  => sanitize_email( $_POST['email_sender_address'] ?? get_option( 'admin_email' ) ),
	);

	update_option( 'mbs_settings', $new_settings );
	echo '<div class="notice notice-success is-dismissible"><p><strong>Cập nhật cài đặt thành công!</strong></p></div>';
}

if ( isset( $_POST['mbs_reseed_data_nonce'] ) && wp_verify_nonce( $_POST['mbs_reseed_data_nonce'], 'mbs_reseed_data' ) ) {
	MBS_Seeder::run();
	echo '<div class="notice notice-success is-dismissible"><p><strong>Đã nạp lại dữ liệu mẫu (Demo Data) thành công!</strong></p></div>';
}

$settings = get_option( 'mbs_settings', array() );
?>
<div class="wrap mbs-admin-wrap">
	<h1><i class="fa-solid fa-gear" style="color: #4f46e5; margin-right: 8px;"></i> Cấu Hình Hệ Thống Đặt Vé Rạp Phim</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'mbs_save_settings', 'mbs_save_settings_nonce' ); ?>

		<!-- Section 1: General & Seat Hold -->
		<div class="mbs-card-box">
			<h3>1. Cài Đặt Chung & Cơ Chế Giữ Chỗ Ghế</h3>
			<table class="form-table">
				<tr>
					<th><label for="seat_lock_duration">Thời Gian Giữ Chỗ Ghế Tạm Thời (phút)</label></th>
					<td>
						<input type="number" name="seat_lock_duration" id="seat_lock_duration" value="<?php echo esc_attr( $settings['seat_lock_duration'] ?? 10 ); ?>" min="3" max="30" class="small-text" /> phút
						<p class="description">Khi khách chọn ghế, hệ thống sẽ tạm khóa ghế này để tránh khách khác chọn trùng. Sau thời gian này ghế sẽ tự động nhả ra nếu chưa thanh toán.</p>
					</td>
				</tr>
				<tr>
					<th><label for="cancel_allowed_before">Thời Gian Cho Phép Hoàn/Hủy Vé (phút)</label></th>
					<td>
						<input type="number" name="cancel_allowed_before" id="cancel_allowed_before" value="<?php echo esc_attr( $settings['cancel_allowed_before'] ?? 120 ); ?>" class="small-text" /> phút trước giờ chiếu
						<p class="description">Khách hàng chỉ được hủy vé nếu còn cách giờ chiếu tối thiểu số phút quy định.</p>
					</td>
				</tr>
				<tr>
					<th><label for="points_rate">Tích Điểm Thành Viên</label></th>
					<td>
						1 điểm thưởng cho mỗi <strong>10.000 VNĐ</strong> thanh toán
					</td>
				</tr>
			</table>
		</div>

		<!-- Section 2: VNPay Sandbox -->
		<div class="mbs-card-box">
			<h3>2. Cổng Thanh Toán VNPay (Sandbox / Live)</h3>
			<table class="form-table">
				<tr>
					<th><label for="vnpay_sandbox">Kích Hoạt Sandbox</label></th>
					<td>
						<label><input type="checkbox" name="vnpay_sandbox" id="vnpay_sandbox" value="1" <?php checked( $settings['vnpay_sandbox'] ?? 1, 1 ); ?> /> Sử dụng môi trường thử nghiệm VNPay Sandbox</label>
					</td>
				</tr>
				<tr>
					<th><label for="vnpay_tmn_code">Mã Website (vnp_TmnCode)</label></th>
					<td><input type="text" name="vnpay_tmn_code" id="vnpay_tmn_code" value="<?php echo esc_attr( $settings['vnpay_tmn_code'] ?? 'DEMOVNPAY' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="vnpay_hash_secret">Chuỗi Bí Mật (vnp_HashSecret)</label></th>
					<td><input type="password" name="vnpay_hash_secret" id="vnpay_hash_secret" value="<?php echo esc_attr( $settings['vnpay_hash_secret'] ?? 'RAOSecretKey123456789DemoVNPaySandbox' ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th><label for="vnpay_url">URL Cổng Thanh Toán</label></th>
					<td><input type="url" name="vnpay_url" id="vnpay_url" value="<?php echo esc_attr( $settings['vnpay_url'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html' ); ?>" class="large-text" /></td>
				</tr>
			</table>
		</div>

		<!-- Section 3: MoMo Sandbox -->
		<div class="mbs-card-box">
			<h3>3. Cổng Thanh Toán Ví MoMo (Sandbox)</h3>
			<table class="form-table">
				<tr>
					<th><label for="momo_sandbox">Kích Hoạt Sandbox</label></th>
					<td>
						<label><input type="checkbox" name="momo_sandbox" id="momo_sandbox" value="1" <?php checked( $settings['momo_sandbox'] ?? 1, 1 ); ?> /> Sử dụng môi trường thử nghiệm MoMo</label>
					</td>
				</tr>
				<tr>
					<th><label for="momo_partner_code">Partner Code</label></th>
					<td><input type="text" name="momo_partner_code" id="momo_partner_code" value="<?php echo esc_attr( $settings['momo_partner_code'] ?? 'MOMODEMO123' ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="momo_access_key">Access Key</label></th>
					<td><input type="text" name="momo_access_key" id="momo_access_key" value="<?php echo esc_attr( $settings['momo_access_key'] ?? 'DemoAccessKeyMomo' ); ?>" class="regular-text" /></td>
				</tr>
			</table>
		</div>

		<!-- Section 4: Email & SMS Notification -->
		<div class="mbs-card-box">
			<h3>4. Cấu Hình Email & SMS Xác Nhận Vé</h3>
			<table class="form-table">
				<tr>
					<th><label for="email_sender_name">Tên Người Gửi Email</label></th>
					<td><input type="text" name="email_sender_name" id="email_sender_name" value="<?php echo esc_attr( $settings['email_sender_name'] ?? get_bloginfo( 'name' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="email_sender_address">Địa Chỉ Email Gửi</label></th>
					<td><input type="email" name="email_sender_address" id="email_sender_address" value="<?php echo esc_attr( $settings['email_sender_address'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th><label for="sms_api_key">SMS Gateway API Key</label></th>
					<td><input type="text" name="sms_api_key" id="sms_api_key" value="<?php echo esc_attr( $settings['sms_api_key'] ?? 'DEMO_SMS_SPEEDSMS_123' ); ?>" class="large-text" /></td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-large">💾 Lưu Toàn Bộ Cấu Hình</button>
		</p>
	</form>

	<!-- Section 5: Demo Data Seeder Utility -->
	<div class="mbs-card-box" style="margin-top: 30px; border-top: 3px solid #ffb800;">
		<h3>🚀 Khởi Tạo Lại Bộ Dữ Liệu Mẫu (Demo Data)</h3>
		<p>Nạp lại 5 phim bom tấn, 2 rạp, 4 phòng chiếu, các suất chiếu trong tuần tới, combo bắp nước và mã giảm giá nếu bạn muốn khôi phục dữ liệu ban đầu.</p>
		<form method="post" action="" onsubmit="return confirm('Bạn có chắc chắn muốn nạp lại bộ dữ liệu mẫu không?');">
			<?php wp_nonce_field( 'mbs_reseed_data', 'mbs_reseed_data_nonce' ); ?>
			<button type="submit" class="button button-secondary">🔄 Nạp Lại Dữ Liệu Mẫu Ngay</button>
		</form>
	</div>
</div>
