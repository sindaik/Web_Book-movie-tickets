<?php
/**
 * Cinema & Room Management + Visual Seat Map Designer
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$cinemas = get_posts( array( 'post_type' => 'cinema', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
$table_rooms = $wpdb->prefix . 'booking_rooms';
$rooms = $wpdb->get_results( "SELECT r.*, p.post_title as cinema_name FROM $table_rooms r JOIN {$wpdb->posts} p ON p.ID = r.cinema_id WHERE r.status = 'active' ORDER BY r.cinema_id ASC, r.id ASC" );
?>
<div class="wrap mbs-admin-wrap">
	<h1><i class="fa-solid fa-building" style="color: #4f46e5; margin-right: 8px;"></i> Quản Lý Rạp & Phòng Chiếu</h1>

	<div class="mbs-cinemas-layout">
		<!-- Left: Rooms List -->
		<div class="mbs-cinemas-list-box">
			<div class="mbs-box-header">
				<h2>Danh Sách Phòng Chiếu</h2>
				<button type="button" class="button button-primary" onclick="mbsNewRoomModal()" style="display:inline-flex;align-items:center;gap:6px;">
					<i class="fa-solid fa-plus"></i> <span>Thêm Phòng Chiếu Mới</span>
				</button>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Tên Phòng</th>
						<th>Thuộc Rạp</th>
						<th>Định Dạng / Loại</th>
						<th>Kích Thước</th>
						<th>Tổng Số Ghế</th>
						<th>Thao Tác</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rooms ) ) : ?>
						<tr><td colspan="6">Chưa có phòng chiếu nào. Vui lòng bấm thêm phòng mới.</td></tr>
					<?php else : ?>
						<?php foreach ( $rooms as $r ) : 
							$total_seats = $r->total_rows * $r->total_cols;
						?>
							<tr>
								<td><strong><?php echo esc_html( $r->room_name ); ?></strong></td>
								<td><?php echo esc_html( $r->cinema_name ); ?></td>
								<td><span class="mbs-badge"><?php echo esc_html( $r->room_type ); ?></span></td>
								<td><?php echo esc_html( $r->total_rows ); ?> hàng × <?php echo esc_html( $r->total_cols ); ?> cột</td>
								<td><strong><?php echo esc_html( $total_seats ); ?></strong> ghế</td>
								<td>
									<button type="button" class="button button-secondary" onclick="mbsEditRoomLayout(<?php echo esc_attr( $r->id ); ?>, <?php echo esc_attr( $r->cinema_id ); ?>, '<?php echo esc_attr( $r->room_name ); ?>', '<?php echo esc_attr( $r->room_type ); ?>', <?php echo esc_attr( $r->total_rows ); ?>, <?php echo esc_attr( $r->total_cols ); ?>)">
										🎨 Thiết Kế Sơ Đồ Ghế
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Right: Visual Room Designer Form & Seat Builder -->
		<div class="mbs-room-editor-box" id="mbs-room-editor-box" style="display:none;">
			<div class="mbs-box-header">
				<h2 id="mbs-editor-title">Cấu Hình Phòng & Sơ Đồ Ghế</h2>
				<button type="button" class="button" onclick="document.getElementById('mbs-room-editor-box').style.display='none'">Đóng</button>
			</div>

			<form id="mbs-room-form" onsubmit="mbsSaveRoom(event)">
				<input type="hidden" id="mbs-room-id" value="0" />
				<div class="mbs-form-row">
					<div class="mbs-form-group">
						<label>Chọn Rạp Chiếu <span class="req">*</span></label>
						<select id="mbs-room-cinema" required>
							<option value="">-- Chọn Rạp --</option>
							<?php foreach ( $cinemas as $c ) : ?>
								<option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="mbs-form-group">
						<label>Tên Phòng Chiếu <span class="req">*</span></label>
						<input type="text" id="mbs-room-name" placeholder="VD: Phòng Chiếu 01 (IMAX Laser)" required />
					</div>
				</div>

				<div class="mbs-form-row">
					<div class="mbs-form-group">
						<label>Loại Phòng</label>
						<select id="mbs-room-type">
							<option value="Standard">Standard 2D</option>
							<option value="IMAX Laser">IMAX Laser</option>
							<option value="3D Hall">3D Hall</option>
							<option value="Gold Class">Gold Class VIP</option>
						</select>
					</div>
					<div class="mbs-form-group">
						<label>Số Hàng Ghế (A-Z)</label>
						<input type="number" id="mbs-room-rows" value="8" min="2" max="26" onchange="mbsRegenerateSeatPreview()" />
					</div>
					<div class="mbs-form-group">
						<label>Số Cột Ghế (1-30)</label>
						<input type="number" id="mbs-room-cols" value="10" min="4" max="30" onchange="mbsRegenerateSeatPreview()" />
					</div>
				</div>

				<!-- Visual Seat Preview & Customizer -->
				<div class="mbs-seat-designer-panel">
					<h3>Click vào ghế để chuyển đổi loại ghế:</h3>
					<div class="mbs-designer-legend">
						<span class="mbs-seat-sample seat-regular"></span> Thường (Click: Regular -> VIP -> Couple)
						<span class="mbs-seat-sample seat-vip" style="margin-left:15px;"></span> VIP (+20k)
						<span class="mbs-seat-sample seat-couple" style="margin-left:15px;"></span> Ghế Đôi (+40k)
					</div>

					<div class="mbs-screen-mini">MÀN HÌNH CHIẾU</div>
					<div id="mbs-designer-seat-grid" class="mbs-designer-grid"></div>
				</div>

				<div class="mbs-editor-actions">
					<button type="submit" class="button button-primary button-large" id="mbs-save-room-btn">💾 Lưu Cấu Hình Phòng & Sơ Đồ Ghế</button>
				</div>
			</form>
		</div>
	</div>
</div>
