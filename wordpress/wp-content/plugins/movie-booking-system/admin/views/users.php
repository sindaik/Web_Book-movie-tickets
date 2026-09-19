<?php
/**
 * User Management View - Movie Booking System
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Bạn không có quyền truy cập trang quản lý người dùng.', 'movie-booking-system' ) );
}

global $wpdb;
$table_orders = $wpdb->prefix . 'booking_orders';

// Role and Search Filters
$role_filter  = sanitize_text_field( $_GET['role'] ?? '' );
$search_query = sanitize_text_field( $_GET['s'] ?? '' );

// User Counts
$user_counts = count_users();
$total_users = $user_counts['total_users'];
$admin_count = $user_counts['avail_roles']['administrator'] ?? 0;
$staff_count = ( $user_counts['avail_roles']['cinema_staff'] ?? 0 ) + ( $user_counts['avail_roles']['cinema_accountant'] ?? 0 );
$customer_count = ( $user_counts['avail_roles']['subscriber'] ?? 0 ) + ( $user_counts['avail_roles']['customer'] ?? 0 );

// Build Query
$query_args = array(
	'number'  => 100,
	'orderby' => 'registered',
	'order'   => 'DESC',
);

if ( ! empty( $role_filter ) ) {
	$query_args['role'] = $role_filter;
}

if ( ! empty( $search_query ) ) {
	// Search by username, display name, email OR phone meta
	$user_ids_by_meta = $wpdb->get_col( $wpdb->prepare(
		"SELECT user_id FROM {$wpdb->usermeta} WHERE (meta_key = 'phone' OR meta_key = 'billing_phone') AND meta_value LIKE %s",
		'%' . $wpdb->esc_like( $search_query ) . '%'
	) );

	$user_ids_by_text = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s",
		'%' . $wpdb->esc_like( $search_query ) . '%',
		'%' . $wpdb->esc_like( $search_query ) . '%',
		'%' . $wpdb->esc_like( $search_query ) . '%'
	) );

	$all_matched_ids = array_unique( array_merge( $user_ids_by_meta, $user_ids_by_text ) );
	if ( empty( $all_matched_ids ) ) {
		$query_args['include'] = array( 0 ); // No results
	} else {
		$query_args['include'] = $all_matched_ids;
	}
}

$users = get_users( $query_args );
$current_user_id = get_current_user_id();
?>

<div class="wrap mbs-admin-wrap">
	<!-- Page Header & Action -->
	<div class="mbs-header-row">
		<div>
			<h1><i class="fa-solid fa-users-gear" style="color: #4f46e5; margin-right: 8px;"></i> Quản Lý Người Dùng & Khách Hàng</h1>
			<p style="margin: 4px 0 0; color: #64748b;">Quản lý tài khoản, phân quyền nhân viên, khách hàng và cấp lại mật khẩu nhanh chóng.</p>
		</div>
		<div>
			<button type="button" class="button button-primary mbs-btn-add-user" onclick="mbsOpenAddUserModal()">
				<i class="fa-solid fa-user-plus"></i>
				<span>Thêm Người Dùng Mới</span>
			</button>
		</div>
	</div>

	<!-- Stats Summary Grid -->
	<div class="mbs-stats-grid" style="margin-bottom: 24px;">
		<div class="mbs-stat-card">
			<div class="mbs-stat-icon" style="color: #6366f1;"><i class="fa-solid fa-users"></i></div>
			<div class="mbs-stat-data">
				<div class="mbs-stat-label">Tổng Người Dùng</div>
				<h2><?php echo esc_html( number_format( $total_users ) ); ?></h2>
			</div>
		</div>

		<div class="mbs-stat-card">
			<div class="mbs-stat-icon" style="color: #10b981;"><i class="fa-solid fa-ticket"></i></div>
			<div class="mbs-stat-data">
				<div class="mbs-stat-label">Khách Hàng / Thành Viên</div>
				<h2><?php echo esc_html( number_format( $customer_count ) ); ?></h2>
			</div>
		</div>

		<div class="mbs-stat-card">
			<div class="mbs-stat-icon" style="color: #0ea5e9;"><i class="fa-solid fa-qrcode"></i></div>
			<div class="mbs-stat-data">
				<div class="mbs-stat-label">Nhân Viên Soát Vé</div>
				<h2><?php echo esc_html( number_format( $staff_count ) ); ?></h2>
			</div>
		</div>

		<div class="mbs-stat-card">
			<div class="mbs-stat-icon" style="color: #e11d48;"><i class="fa-solid fa-shield-halved"></i></div>
			<div class="mbs-stat-data">
				<div class="mbs-stat-label">Quản Trị Viên (Admin)</div>
				<h2><?php echo esc_html( number_format( $admin_count ) ); ?></h2>
			</div>
		</div>
	</div>

	<!-- Search & Filter Bar -->
	<form method="get" class="mbs-filter-bar">
		<input type="hidden" name="page" value="mbs-users" />
		<input type="text" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Tìm tên, username, email, SĐT..." style="min-width: 260px;" />
		
		<select name="role">
			<option value="">-- Tất cả vai trò --</option>
			<option value="subscriber" <?php selected( $role_filter, 'subscriber' ); ?>>👤 Khách Hàng (Subscriber)</option>
			<option value="cinema_staff" <?php selected( $role_filter, 'cinema_staff' ); ?>>🔍 Nhân Viên Rạp Phim</option>
			<option value="cinema_accountant" <?php selected( $role_filter, 'cinema_accountant' ); ?>>💰 Kế Toán</option>
			<option value="administrator" <?php selected( $role_filter, 'administrator' ); ?>>👑 Quản Trị Viên (Administrator)</option>
		</select>

		<button type="submit" class="button button-primary">Lọc Dữ Liệu</button>

		<?php if ( ! empty( $role_filter ) || ! empty( $search_query ) ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mbs-users' ) ); ?>" class="button">Xóa Lọc</a>
		<?php endif; ?>
	</form>

	<!-- Users Table -->
	<div class="mbs-card-box" style="padding: 0; overflow: hidden;">
		<table class="wp-list-table widefat fixed striped" style="border: none;">
			<thead>
				<tr>
					<th width="50" style="text-align:center;">Avatar</th>
					<th width="160">Tên Đăng Nhập</th>
					<th>Họ Và Tên</th>
					<th>Liên Hệ (Email / SĐT)</th>
					<th width="150">Vai Trò</th>
					<th width="130">Vé Đã Đặt</th>
					<th width="110">Ngày Tham Gia</th>
					<th width="200" style="text-align: right;">Thao Tác</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $users ) ) : ?>
					<tr>
						<td colspan="8" style="text-align: center; padding: 30px; color: #64748b;">
							Không tìm thấy người dùng nào phù hợp với bộ lọc tìm kiếm.
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $users as $u ) : 
						$phone = get_user_meta( $u->ID, 'phone', true );
						if ( empty( $phone ) ) {
							$phone = get_user_meta( $u->ID, 'billing_phone', true );
						}

						$user_roles = (array) $u->roles;
						$primary_role = ! empty( $user_roles ) ? reset( $user_roles ) : 'subscriber';

						// Role badge presentation
						$badge_class = 'mbs-badge-customer';
						$badge_text  = '👤 Khách Hàng';
						if ( $primary_role === 'administrator' ) {
							$badge_class = 'mbs-badge-admin';
							$badge_text  = '👑 Quản Trị Viên';
						} elseif ( $primary_role === 'cinema_staff' ) {
							$badge_class = 'mbs-badge-staff';
							$badge_text  = '🔍 Nhân Viên Soát Vé';
						} elseif ( $primary_role === 'cinema_accountant' ) {
							$badge_class = 'mbs-badge-warning';
							$badge_text  = '💰 Kế Toán';
						}

						// Order count & spent
						$order_stats = $wpdb->get_row( $wpdb->prepare(
							"SELECT COUNT(*) as count, SUM(final_amount) as total FROM $table_orders WHERE (user_id = %d OR customer_email = %s) AND payment_status = 'paid'",
							$u->ID,
							$u->user_email
						) );
						$order_count = (int) ( $order_stats->count ?? 0 );
						$total_spent = (float) ( $order_stats->total ?? 0 );
						$is_me = ( $u->ID === $current_user_id );
					?>
						<tr>
							<td style="text-align:center; vertical-align: middle;">
								<div class="mbs-user-avatar">
									<?php echo get_avatar( $u->ID, 36 ); ?>
								</div>
							</td>
							<td style="vertical-align: middle;">
								<strong><?php echo esc_html( $u->user_login ); ?></strong>
								<?php if ( $is_me ) : ?>
									<span class="mbs-badge-current">(Bạn)</span>
								<?php endif; ?>
								<div style="color: #94a3b8; font-size: 11px;">ID: #<?php echo esc_html( $u->ID ); ?></div>
							</td>
							<td style="vertical-align: middle;">
								<strong style="color: #1e293b;"><?php echo esc_html( $u->display_name ?: 'Chưa đặt tên' ); ?></strong>
							</td>
							<td style="vertical-align: middle;">
								<div><a href="mailto:<?php echo esc_attr( $u->user_email ); ?>" style="text-decoration:none;">✉️ <?php echo esc_html( $u->user_email ); ?></a></div>
								<div style="margin-top: 3px; font-size: 12px; color: #475569;">
									📞 <?php echo esc_html( $phone ?: 'Chưa có SĐT' ); ?>
								</div>
							</td>
							<td style="vertical-align: middle;">
								<span class="mbs-user-role-pill <?php echo esc_attr( $badge_class ); ?>">
									<?php echo esc_html( $badge_text ); ?>
								</span>
							</td>
							<td style="vertical-align: middle;">
								<?php if ( $order_count > 0 ) : ?>
									<strong style="color: #15803d;"><?php echo esc_html( $order_count ); ?> đơn</strong>
									<div style="font-size: 11px; color: #64748b;"><?php echo esc_html( number_format( $total_spent, 0, ',', '.' ) . 'đ' ); ?></div>
								<?php else : ?>
									<span style="color: #94a3b8; font-size: 12px;">Chưa mua vé</span>
								<?php endif; ?>
							</td>
							<td style="vertical-align: middle; color: #64748b; font-size: 12px;">
								<?php echo esc_html( date( 'd/m/Y', strtotime( $u->user_registered ) ) ); ?>
							</td>
							<td style="text-align: right; vertical-align: middle;">
								<div style="display: inline-flex; gap: 4px;">
									<button type="button" class="button button-small" onclick="mbsOpenEditUserModal(<?php echo esc_attr( $u->ID ); ?>)" title="Chỉnh sửa thông tin">
										<i class="fa-solid fa-pen-to-square"></i> Sửa
									</button>
									<button type="button" class="button button-small" onclick="mbsOpenPasswordModal(<?php echo esc_attr( $u->ID ); ?>, '<?php echo esc_js( $u->user_login ); ?>')" title="Cấp lại mật khẩu mới cho user" style="color: #d97706;">
										<i class="fa-solid fa-key"></i> Đổi Pass
									</button>
									<?php if ( ! $is_me ) : ?>
										<button type="button" class="button button-small button-link-delete" onclick="mbsDeleteUser(<?php echo esc_attr( $u->ID ); ?>, '<?php echo esc_js( $u->user_login ); ?>')" title="Xóa người dùng này">
											<i class="fa-solid fa-trash-can"></i> Xóa
										</button>
									<?php else : ?>
										<span style="display:inline-block; width: 45px;"></span>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- =============================================================
     1. Modal: Thêm Người Dùng Mới
     ============================================================= -->
<div id="mbs-add-user-modal" class="mbs-admin-modal" style="display: none;">
	<div class="mbs-admin-modal-overlay" onclick="mbsCloseAdminModal('mbs-add-user-modal')"></div>
	<div class="mbs-admin-modal-card" style="max-width: 540px;">
		<button type="button" class="mbs-modal-close-btn" onclick="mbsCloseAdminModal('mbs-add-user-modal')">&times;</button>
		<h3 style="margin-top:0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display:flex; align-items:center; gap:8px;">
			<i class="fa-solid fa-user-plus" style="color:#4f46e5;"></i> Thêm Người Dùng Mới
		</h3>

		<form id="mbs-add-user-form" onsubmit="mbsSaveNewUser(event)">
			<div class="mbs-form-row">
				<div class="mbs-form-group">
					<label>Tên Đăng Nhập (Username) <span class="req">*</span></label>
					<input type="text" id="mbs-new-username" required placeholder="VD: ngocdai123" />
					<small style="color: #64748b; font-size: 11px;">Viết liền không dấu, không khoảng trắng</small>
				</div>
				<div class="mbs-form-group">
					<label>Địa Chỉ Email <span class="req">*</span></label>
					<input type="email" id="mbs-new-email" required placeholder="VD: customer@gmail.com" />
				</div>
			</div>

			<div class="mbs-form-row">
				<div class="mbs-form-group">
					<label>Họ Và Tên</label>
					<input type="text" id="mbs-new-displayname" placeholder="VD: Nguyễn Ngọc Đại" />
				</div>
				<div class="mbs-form-group">
					<label>Số Điện Thoại</label>
					<input type="text" id="mbs-new-phone" placeholder="VD: 0912345678" />
				</div>
			</div>

			<div class="mbs-form-group" style="margin-bottom: 16px;">
				<label>Vai Trò Trong Hệ Thống <span class="req">*</span></label>
				<select id="mbs-new-role">
					<option value="subscriber">👤 Khách Hàng / Thành Viên (Subscriber)</option>
					<option value="cinema_staff">🔍 Nhân Viên Soát Vé (Cinema Staff)</option>
					<option value="cinema_accountant">💰 Kế Toán Rạp (Cinema Accountant)</option>
					<option value="administrator">👑 Quản Trị Viên Toàn Quyền (Administrator)</option>
				</select>
			</div>

			<div class="mbs-form-group" style="margin-bottom: 20px;">
				<label>Mật Khẩu Khởi Tạo <span class="req">*</span></label>
				<div class="mbs-pass-input-row">
					<input type="text" id="mbs-new-password" required placeholder="Nhập hoặc bấm Tạo Ngẫu Nhiên" style="flex:1;" />
					<button type="button" class="button" onclick="mbsGenerateNewUserPass()">🎲 Tạo Ngẫu Nhiên</button>
				</div>
			</div>

			<div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
				<button type="button" class="button button-large" onclick="mbsCloseAdminModal('mbs-add-user-modal')">Hủy</button>
				<button type="submit" class="button button-primary button-large" id="mbs-btn-submit-new-user">
					💾 Tạo Người Dùng
				</button>
			</div>
		</form>
	</div>
</div>

<!-- =============================================================
     2. Modal: Chỉnh Sửa Thông Tin Người Dùng
     ============================================================= -->
<div id="mbs-edit-user-modal" class="mbs-admin-modal" style="display: none;">
	<div class="mbs-admin-modal-overlay" onclick="mbsCloseAdminModal('mbs-edit-user-modal')"></div>
	<div class="mbs-admin-modal-card" style="max-width: 540px;">
		<button type="button" class="mbs-modal-close-btn" onclick="mbsCloseAdminModal('mbs-edit-user-modal')">&times;</button>
		<h3 style="margin-top:0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display:flex; align-items:center; gap:8px;">
			<i class="fa-solid fa-user-pen" style="color:#0284c7;"></i> Chỉnh Sửa Người Dùng: <span id="mbs-edit-title-username" style="color:#0284c7;"></span>
		</h3>

		<form id="mbs-edit-user-form" onsubmit="mbsSaveEditUser(event)">
			<input type="hidden" id="mbs-edit-user-id" value="" />

			<div class="mbs-form-row">
				<div class="mbs-form-group">
					<label>Tên Đăng Nhập</label>
					<input type="text" id="mbs-edit-username" disabled style="background:#f1f5f9; cursor:not-allowed;" />
					<small style="color: #94a3b8; font-size: 11px;">Tên đăng nhập không thể thay đổi</small>
				</div>
				<div class="mbs-form-group">
					<label>Họ Và Tên</label>
					<input type="text" id="mbs-edit-displayname" placeholder="Họ và tên hiển thị" />
				</div>
			</div>

			<div class="mbs-form-row">
				<div class="mbs-form-group">
					<label>Địa Chỉ Email <span class="req">*</span></label>
					<input type="email" id="mbs-edit-email" required />
				</div>
				<div class="mbs-form-group">
					<label>Số Điện Thoại</label>
					<input type="text" id="mbs-edit-phone" placeholder="Số điện thoại" />
				</div>
			</div>

			<div class="mbs-form-group" style="margin-bottom: 20px;">
				<label>Vai Trò Trong Hệ Thống</label>
				<select id="mbs-edit-role">
					<option value="subscriber">👤 Khách Hàng / Thành Viên (Subscriber)</option>
					<option value="cinema_staff">🔍 Nhân Viên Soát Vé (Cinema Staff)</option>
					<option value="cinema_accountant">💰 Kế Toán Rạp (Cinema Accountant)</option>
					<option value="administrator">👑 Quản Trị Viên Toàn Quyền (Administrator)</option>
				</select>
			</div>

			<div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
				<button type="button" class="button button-large" onclick="mbsCloseAdminModal('mbs-edit-user-modal')">Hủy</button>
				<button type="submit" class="button button-primary button-large" id="mbs-btn-submit-edit-user">
					💾 Lưu Thay Đổi
				</button>
			</div>
		</form>
	</div>
</div>

<!-- =============================================================
     3. Modal: Cập Nhật / Đổi Mật Khẩu Nhanh Cho User
     ============================================================= -->
<div id="mbs-password-user-modal" class="mbs-admin-modal" style="display: none;">
	<div class="mbs-admin-modal-overlay" onclick="mbsCloseAdminModal('mbs-password-user-modal')"></div>
	<div class="mbs-admin-modal-card" style="max-width: 500px;">
		<button type="button" class="mbs-modal-close-btn" onclick="mbsCloseAdminModal('mbs-password-user-modal')">&times;</button>
		<h3 style="margin-top:0; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; display:flex; align-items:center; gap:8px;">
			<i class="fa-solid fa-key" style="color: #e11d48;"></i> Cấp Lại Mật Khẩu Cho: <span id="mbs-pass-title-username" style="color: #e11d48;"></span>
		</h3>

		<form id="mbs-password-user-form" onsubmit="mbsSaveUserPassword(event)">
			<input type="hidden" id="mbs-pass-user-id" value="" />

			<p style="color: #64748b; font-size: 13px; margin: 0 0 16px;">
				Nhập mật khẩu mới hoặc sử dụng nút <strong>"Tạo Mật Khẩu Tự Động"</strong> để tạo mật khẩu an toàn. Sau khi lưu, bạn có thể sao chép mật khẩu gửi trực tiếp cho khách hàng.
			</p>

			<div class="mbs-form-group" style="margin-bottom: 16px;">
				<label>Mật Khẩu Mới <span class="req">*</span></label>
				<div class="mbs-pass-input-row">
					<input type="text" id="mbs-pass-new-val" required placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" style="flex:1; font-family: monospace; font-size: 15px;" />
					<button type="button" class="button" onclick="mbsGenerateModalPassword()" title="Tạo mật khẩu an toàn ngẫu nhiên">
						<i class="fa-solid fa-dice"></i> Tạo Tự Động
					</button>
					<button type="button" class="button" onclick="mbsCopyModalPassword()" title="Sao chép mật khẩu vào bộ nhớ tạm">
						📋 Sao Chép
					</button>
				</div>
			</div>

			<!-- Success Notification Container -->
			<div id="mbs-pass-success-box" style="display:none; background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px; margin-bottom:16px; color:#166534; font-size:13px;">
				✅ <strong>Cập nhật thành công!</strong> Mật khẩu mới: <code id="mbs-pass-copied-val" style="background:#dcfce7; padding:2px 6px; border-radius:4px; font-weight:700;"></code>
			</div>

			<div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
				<button type="button" class="button button-large" onclick="mbsCloseAdminModal('mbs-password-user-modal')">Đóng</button>
				<button type="submit" class="button button-primary button-large" id="mbs-btn-submit-pass">
					💾 Lưu Mật Khẩu Mới
				</button>
			</div>
		</form>
	</div>
</div>

<style>
.mbs-admin-modal {
	position: fixed !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	bottom: 0 !important;
	width: 100vw !important;
	height: 100vh !important;
	z-index: 9999999 !important;
	display: none;
	align-items: center !important;
	justify-content: center !important;
}
.mbs-admin-modal.mbs-modal-show {
	display: flex !important;
}
.mbs-admin-modal-overlay {
	position: absolute !important;
	inset: 0 !important;
	background: rgba(15, 23, 42, 0.72) !important;
	backdrop-filter: blur(5px) !important;
	-webkit-backdrop-filter: blur(5px) !important;
}
.mbs-admin-modal-card {
	position: relative !important;
	z-index: 10 !important;
	background: #ffffff !important;
	border-radius: 12px !important;
	padding: 26px !important;
	width: 92% !important;
	max-width: 560px !important;
	max-height: 90vh !important;
	overflow-y: auto !important;
	box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45) !important;
	animation: mbsModalZoom 0.2s ease-out !important;
}
@keyframes mbsModalZoom {
	from { transform: scale(0.95); opacity: 0; }
	to { transform: scale(1); opacity: 1; }
}
.mbs-modal-close-btn {
	position: absolute !important;
	top: 14px !important;
	right: 16px !important;
	background: none !important;
	border: none !important;
	font-size: 26px !important;
	cursor: pointer !important;
	color: #64748b !important;
	line-height: 1 !important;
}
.mbs-modal-close-btn:hover {
	color: #e11d48 !important;
}
.mbs-pass-input-row {
	display: flex;
	gap: 6px;
	align-items: center;
}
.mbs-user-role-pill {
	display: inline-flex;
	align-items: center;
	padding: 4px 10px;
	border-radius: 14px;
	font-size: 11.5px;
	font-weight: 600;
}
.mbs-badge-admin {
	background: #ffe4e6;
	color: #be123c;
	border: 1px solid #fecdd3;
}
.mbs-badge-staff {
	background: #e0f2fe;
	color: #0369a1;
	border: 1px solid #bae6fd;
}
.mbs-badge-customer {
	background: #f0fdf4;
	color: #15803d;
	border: 1px solid #bbf7d0;
}
.mbs-badge-current {
	display: inline-block;
	background: #fef3c7;
	color: #92400e;
	font-size: 10px;
	font-weight: 700;
	padding: 1px 6px;
	border-radius: 4px;
	margin-left: 4px;
	vertical-align: middle;
}
</style>

<script>
(function() {
	var ajaxUrl = (typeof mbsAdmin !== 'undefined' && mbsAdmin.ajaxUrl) ? mbsAdmin.ajaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wordpress/wp-admin/admin-ajax.php');
	var nonce   = (typeof mbsAdmin !== 'undefined' && mbsAdmin.nonce) ? mbsAdmin.nonce : '<?php echo wp_create_nonce("mbs_admin_nonce"); ?>';

	function genPass() {
		var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		var lower = 'abcdefghijkmnopqrstuvwxyz';
		var digits = '23456789';
		var special = '@#$%&*';
		var pass = '';
		pass += upper.charAt(Math.floor(Math.random() * upper.length));
		pass += lower.charAt(Math.floor(Math.random() * lower.length));
		pass += digits.charAt(Math.floor(Math.random() * digits.length));
		pass += special.charAt(Math.floor(Math.random() * special.length));
		var all = upper + lower + digits + special;
		for (var i = 0; i < 6; i++) {
			pass += all.charAt(Math.floor(Math.random() * all.length));
		}
		return pass;
	}

	window.mbsCloseAdminModal = function(id) {
		var modal = document.getElementById(id);
		if (modal) {
			modal.classList.remove('mbs-modal-show');
			modal.style.display = 'none';
		}
	};

	window.mbsOpenAddUserModal = function() {
		var form = document.getElementById('mbs-add-user-form');
		if (form) form.reset();
		var passInput = document.getElementById('mbs-new-password');
		if (passInput) passInput.value = genPass();
		var modal = document.getElementById('mbs-add-user-modal');
		if (modal) {
			modal.classList.add('mbs-modal-show');
			modal.style.display = 'flex';
		}
	};

	window.mbsGenerateNewUserPass = function() {
		var passInput = document.getElementById('mbs-new-password');
		if (passInput) passInput.value = genPass();
	};

	window.mbsSaveNewUser = function(e) {
		e.preventDefault();
		var btn = document.getElementById('mbs-btn-submit-new-user');
		if (btn) {
			btn.disabled = true;
			btn.textContent = 'Đang tạo người dùng...';
		}

		var formData = new FormData();
		formData.append('action', 'mbs_add_user_ajax');
		formData.append('_ajax_nonce', nonce);
		formData.append('username', document.getElementById('mbs-new-username').value.trim());
		formData.append('email', document.getElementById('mbs-new-email').value.trim());
		formData.append('display_name', document.getElementById('mbs-new-displayname').value.trim());
		formData.append('phone', document.getElementById('mbs-new-phone').value.trim());
		formData.append('role', document.getElementById('mbs-new-role').value);
		formData.append('password', document.getElementById('mbs-new-password').value.trim());

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Tạo Người Dùng';
			}
			if (res.success) {
				alert(res.data && res.data.message ? res.data.message : 'Thêm người dùng mới thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data && res.data.message ? res.data.message : 'Không thể tạo người dùng.'));
			}
		})
		.catch(function(err) {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Tạo Người Dùng';
			}
			alert('Lỗi kết nối máy chủ!');
		});
	};

	window.mbsOpenEditUserModal = function(userId) {
		var formData = new FormData();
		formData.append('action', 'mbs_get_user_ajax');
		formData.append('_ajax_nonce', nonce);
		formData.append('user_id', userId);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success && res.data) {
				var u = res.data;
				document.getElementById('mbs-edit-user-id').value = u.id;
				document.getElementById('mbs-edit-title-username').textContent = u.username;
				document.getElementById('mbs-edit-username').value = u.username;
				document.getElementById('mbs-edit-displayname').value = u.display_name || '';
				document.getElementById('mbs-edit-email').value = u.email || '';
				document.getElementById('mbs-edit-phone').value = u.phone || '';
				document.getElementById('mbs-edit-role').value = u.role || 'subscriber';

				var modal = document.getElementById('mbs-edit-user-modal');
				if (modal) {
					modal.classList.add('mbs-modal-show');
					modal.style.display = 'flex';
				}
			} else {
				alert('Lỗi: ' + (res.data && res.data.message ? res.data.message : 'Không tìm thấy người dùng.'));
			}
		})
		.catch(function() {
			alert('Lỗi kết nối máy chủ!');
		});
	};

	window.mbsSaveEditUser = function(e) {
		e.preventDefault();
		var btn = document.getElementById('mbs-btn-submit-edit-user');
		if (btn) {
			btn.disabled = true;
			btn.textContent = 'Đang lưu...';
		}

		var formData = new FormData();
		formData.append('action', 'mbs_update_user_ajax');
		formData.append('_ajax_nonce', nonce);
		formData.append('user_id', document.getElementById('mbs-edit-user-id').value);
		formData.append('display_name', document.getElementById('mbs-edit-displayname').value.trim());
		formData.append('email', document.getElementById('mbs-edit-email').value.trim());
		formData.append('phone', document.getElementById('mbs-edit-phone').value.trim());
		formData.append('role', document.getElementById('mbs-edit-role').value);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Lưu Thay Đổi';
			}
			if (res.success) {
				alert(res.data && res.data.message ? res.data.message : 'Cập nhật thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data && res.data.message ? res.data.message : 'Không thể cập nhật.'));
			}
		})
		.catch(function() {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Lưu Thay Đổi';
			}
			alert('Lỗi kết nối máy chủ!');
		});
	};

	window.mbsOpenPasswordModal = function(userId, username) {
		document.getElementById('mbs-pass-user-id').value = userId;
		document.getElementById('mbs-pass-title-username').textContent = username;
		var successBox = document.getElementById('mbs-pass-success-box');
		if (successBox) successBox.style.display = 'none';
		document.getElementById('mbs-pass-new-val').value = genPass();

		var modal = document.getElementById('mbs-password-user-modal');
		if (modal) {
			modal.classList.add('mbs-modal-show');
			modal.style.display = 'flex';
		}
	};

	window.mbsGenerateModalPassword = function() {
		document.getElementById('mbs-pass-new-val').value = genPass();
		var successBox = document.getElementById('mbs-pass-success-box');
		if (successBox) successBox.style.display = 'none';
	};

	window.mbsCopyModalPassword = function() {
		var val = document.getElementById('mbs-pass-new-val').value;
		if (!val) {
			alert('Chưa có mật khẩu để sao chép!');
			return;
		}
		if (navigator.clipboard) {
			navigator.clipboard.writeText(val).then(function() {
				alert('Đã sao chép mật khẩu vào bộ nhớ tạm: ' + val);
			}).catch(function() {
				prompt('Nhấn Ctrl+C để sao chép mật khẩu:', val);
			});
		} else {
			prompt('Nhấn Ctrl+C để sao chép mật khẩu:', val);
		}
	};

	window.mbsSaveUserPassword = function(e) {
		e.preventDefault();
		var newPass = document.getElementById('mbs-pass-new-val').value.trim();
		if (newPass.length < 6) {
			alert('Mật khẩu tối thiểu phải 6 ký tự!');
			return;
		}

		var btn = document.getElementById('mbs-btn-submit-pass');
		if (btn) {
			btn.disabled = true;
			btn.textContent = 'Đang cập nhật...';
		}

		var formData = new FormData();
		formData.append('action', 'mbs_update_user_password_ajax');
		formData.append('_ajax_nonce', nonce);
		formData.append('user_id', document.getElementById('mbs-pass-user-id').value);
		formData.append('new_password', newPass);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Lưu Mật Khẩu Mới';
			}
			if (res.success) {
				var copyVal = document.getElementById('mbs-pass-copied-val');
				if (copyVal) copyVal.textContent = newPass;
				var successBox = document.getElementById('mbs-pass-success-box');
				if (successBox) successBox.style.display = 'block';
				alert('Đã cập nhật mật khẩu mới thành công!\nMật khẩu mới: ' + newPass);
			} else {
				alert('Lỗi: ' + (res.data && res.data.message ? res.data.message : 'Không thể đổi mật khẩu.'));
			}
		})
		.catch(function() {
			if (btn) {
				btn.disabled = false;
				btn.textContent = '💾 Lưu Mật Khẩu Mới';
			}
			alert('Lỗi kết nối máy chủ!');
		});
	};

	window.mbsDeleteUser = function(userId, username) {
		if (!confirm('Bạn có chắc chắn muốn xóa vĩnh viễn người dùng "' + username + '" (ID: #' + userId + ')?\nThao tác này không thể hoàn tác!')) {
			return;
		}

		var formData = new FormData();
		formData.append('action', 'mbs_delete_user_ajax');
		formData.append('_ajax_nonce', nonce);
		formData.append('user_id', userId);

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				alert(res.data && res.data.message ? res.data.message : 'Đã xóa người dùng thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data && res.data.message ? res.data.message : 'Không thể xóa người dùng.'));
			}
		})
		.catch(function() {
			alert('Lỗi kết nối máy chủ!');
		});
	};
})();
</script>

