<?php
/**
 * Title: Footer
 * Slug: twentytwentyfive/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Luxury Cinema 4-Column Footer with FontAwesome Icons.
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

$home_url = home_url( '/' );
?>

<footer class="cinema-footer-wrapper">
	<!-- 1. VIP Club / Newsletter Banner -->
	<div class="cinema-newsletter-bar">
		<div class="cinema-footer-container cinema-newsletter-inner">
			<div class="cinema-newsletter-info">
				<div class="cinema-newsletter-badge"><i class="fa-solid fa-gift"></i> ĐẶC QUYỀN THÀNH VIÊN</div>
				<h3 class="cinema-newsletter-title">Gia nhập CINEMAGIC Club – Tặng ngay 01 Vé 2D & Bắp ngọt</h3>
				<p class="cinema-newsletter-desc">Nhận lịch chiếu sớm nhất, tích lũy 10% giá trị vé đổi quà và ưu đãi thành viên hàng tuần.</p>
			</div>
			<form class="cinema-newsletter-form" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký thành viên CINEMAGIC Club! Mã ưu đãi vé 2D miễn phí đã được ghi nhận.');">
				<div class="cinema-newsletter-input-group">
					<input type="email" placeholder="Nhập địa chỉ email của bạn..." required class="cinema-newsletter-input" />
					<button type="submit" class="cinema-newsletter-btn">Đăng Ký Ngay</button>
				</div>
			</form>
		</div>
	</div>

	<!-- 2. Main 4-Column Footer -->
	<div class="cinema-main-footer">
		<div class="cinema-footer-container cinema-footer-grid">
			<!-- Col 1: About & Info -->
			<div class="cinema-footer-col cinema-footer-col-about">
				<a href="<?php echo esc_url( $home_url ); ?>" class="cinema-brand-logo cinema-footer-logo">
					<div class="cinema-logo-symbol"><i class="fa-solid fa-clapperboard"></i></div>
					<div class="cinema-logo-text-group">
						<div class="cinema-logo-title">CINE<span class="cinema-logo-glow">MAGIC</span></div>
						<div class="cinema-logo-subtitle">RẠP CHIẾU PHIM QUỐC TẾ</div>
					</div>
				</a>
				<p class="cinema-footer-about-text">
					Hệ thống cụm rạp chiếu phim chuẩn quốc tế với phòng chiếu IMAX Laser màn hình siêu lớn, âm thanh Dolby Atmos đa chiều sống động và phòng VIP Gold Class thượng lưu.
				</p>
				<div class="cinema-contact-list">
					<div class="cinema-contact-item">
						<i class="fa-solid fa-location-dot cinema-contact-fa"></i>
						<span>Tầng 6, TTTM Vincom Center, 191 Bà Triệu, Hà Nội</span>
					</div>
					<div class="cinema-contact-item">
						<i class="fa-solid fa-phone cinema-contact-fa"></i>
						<span>Hotline: <strong class="cinema-highlight">1900 6868</strong> (08:00 - 23:00)</span>
					</div>
					<div class="cinema-contact-item">
						<i class="fa-solid fa-envelope cinema-contact-fa"></i>
						<span>Email hỗ trợ: <strong class="cinema-highlight">cskh@cinemagic.vn</strong></span>
					</div>
				</div>
			</div>

			<!-- Col 2: Cinema Network -->
			<div class="cinema-footer-col">
				<h4 class="cinema-footer-heading"><i class="fa-solid fa-city"></i> Hệ Thống Cụm Rạp</h4>
				<ul class="cinema-footer-links">
					<li><a href="#cinemas"><i class="fa-solid fa-location-dot cinema-link-fa"></i> Cinemagic Vincom Bà Triệu (Hà Nội)</a></li>
					<li><a href="#cinemas"><i class="fa-solid fa-location-dot cinema-link-fa"></i> Cinemagic Landmark 81 (TP.HCM)</a></li>
					<li><a href="#cinemas"><i class="fa-solid fa-location-dot cinema-link-fa"></i> Cinemagic Royal City (Hà Nội)</a></li>
					<li><a href="#cinemas"><i class="fa-solid fa-location-dot cinema-link-fa"></i> Cinemagic Dragon City (Đà Nẵng)</a></li>
					<li><a href="#cinemas"><i class="fa-solid fa-location-dot cinema-link-fa"></i> Cinemagic Thảo Điền Pearl (TP.HCM)</a></li>
					<li><a href="#vip"><i class="fa-solid fa-gem cinema-link-fa"></i> Phòng Chiếu VIP Gold Class</a></li>
					<li><a href="#events"><i class="fa-solid fa-crown cinema-link-fa"></i> Thuê Rạp Tổ Chức Sự Kiện & Sinh Nhật</a></li>
				</ul>
			</div>

			<!-- Col 3: QUY ĐỊNH & ĐIỀU KHOẢN (Chuẩn BHD Star) -->
			<div class="cinema-footer-col cinema-footer-col-terms">
				<h4 class="cinema-footer-heading cinema-footer-heading-terms">QUY ĐỊNH & ĐIỀU KHOẢN</h4>
				<div class="cinema-footer-green-bar"></div>
				<ul class="cinema-footer-links cinema-terms-links">
					<li><a href="<?php echo esc_url( home_url( '/quy-dinh-thanh-vien/' ) ); ?>">Quy định thành viên</a></li>
					<li><a href="<?php echo esc_url( home_url( '/dieu-khoan/' ) ); ?>">Điều khoản</a></li>
					<li><a href="<?php echo esc_url( home_url( '/huong-dan-dat-ve/' ) ); ?>">Hướng dẫn đặt vé trực tuyến</a></li>
					<li><a href="<?php echo esc_url( home_url( '/quy-dinh-va-chinh-sach-chung/' ) ); ?>">Quy định và chính sách chung</a></li>
					<li><a href="<?php echo esc_url( home_url( '/chinh-sach-bao-ve-thong-tin-ca-nhan/' ) ); ?>">Chính sách bảo vệ thông tin cá nhân của người tiêu dùng</a></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- 3. Copyright & Legal Bar -->
	<div class="cinema-bottom-bar">
		<div class="cinema-footer-container cinema-bottom-bar-inner">
			<div class="cinema-copyright">
				© 2026 <strong>CINEMAGIC ENTERTAINMENT VIETNAM</strong>. Toàn bộ quyền được bảo lưu.
			</div>
			<div class="cinema-legal-badge">
				<span class="cinema-bct-badge"><i class="fa-solid fa-shield-halved"></i> Đã Đăng Ký Bộ Công Thương</span>
			</div>
		</div>
	</div>
</footer>
