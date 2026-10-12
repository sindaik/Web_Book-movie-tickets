<?php
/**
 * Staff Ticket Check-in View
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap mbs-admin-wrap">
	<h1><i class="fa-solid fa-qrcode" style="color: #4f46e5; margin-right: 8px;"></i> Quầy Soát Vé & Quét Mã QR</h1>
	<p class="description">Giao diện chuyên dụng dành cho nhân viên rạp soát vé bằng Camera hoặc đầu đọc mã vạch / nhập mã vé.</p>

	<div class="mbs-admin-checkin-grid">
		<!-- Scanner Box -->
		<div class="mbs-card-box mbs-scanner-panel">
			<h3><i class="fa-solid fa-camera" style="color:#0284c7;margin-right:6px;"></i> Camera Quét Mã QR Vé</h3>
			<div id="mbs-admin-qr-reader" class="mbs-camera-box"></div>
			<div class="mbs-camera-buttons" style="display:flex;gap:8px;margin-top:12px;">
				<button type="button" id="mbs-admin-start-cam" class="button button-primary button-large" style="display:inline-flex;align-items:center;gap:6px;">
					<i class="fa-solid fa-play"></i> <span>Mở Camera Quét QR</span>
				</button>
				<button type="button" id="mbs-admin-stop-cam" class="button button-secondary button-large" style="display:none;align-items:center;gap:6px;">
					<i class="fa-solid fa-stop"></i> <span>Tắt Camera</span>
				</button>
			</div>

			<hr style="margin: 20px 0; border: 0; border-top: 1px dashed #cbd5e1;" />

			<h3><i class="fa-solid fa-keyboard" style="color:#6366f1;margin-right:6px;"></i> Hoặc Nhập Mã Vé Thủ Công</h3>
			<form id="mbs-admin-manual-checkin" onsubmit="mbsAdminCheckinSubmit(event)">
				<div style="display: flex; gap: 10px;">
					<input type="text" id="mbs-admin-code-input" class="large-text" placeholder="Nhập mã vé (VD: TK2609...)" required autofocus />
					<button type="submit" class="button button-primary button-large" id="mbs-admin-checkin-btn">Xác Thực Vé</button>
				</div>
			</form>
		</div>

		<!-- Result Box -->
		<div class="mbs-card-box mbs-result-panel">
			<h3>Thông Tin Xác Thực</h3>
			<div id="mbs-admin-checkin-output" class="mbs-checkin-empty-state">
				<span style="font-size: 48px;">🎫</span>
				<p>Đang chờ quét vé...<br>Kết quả soát vé sẽ hiển thị tại đây.</p>
			</div>
		</div>
	</div>
</div>
