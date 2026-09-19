<?php
/**
 * Title: Header
 * Slug: twentytwentyfive/header
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Modern Luxury Cinema Header with Topbar, Cinema Location Selector, Navigation & Full Auth Modal.
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

$home_url   = home_url( '/' );
$ve_cua_toi = home_url( '/ve-cua-toi/' );
$soat_ve    = home_url( '/soat-ve/' );
$is_logged  = is_user_logged_in();
$curr_user  = wp_get_current_user();
?>

<!-- Cinema Global Header (Single-Row Modern Bar) -->
<header class="cinema-header-wrapper">
	<nav class="cinema-main-nav">
		<div class="cinema-header-container cinema-main-nav-inner">
			<!-- Brand Logo -->
			<a href="<?php echo esc_url( $home_url ); ?>" class="cinema-brand-logo">
				<div class="cinema-logo-symbol"><i class="fa-solid fa-clapperboard"></i></div>
				<div class="cinema-logo-text-group">
					<div class="cinema-logo-title">CINE<span class="cinema-logo-glow">MAGIC</span></div>
					<div class="cinema-logo-subtitle">RẠP CHIẾU PHIM QUỐC TẾ</div>
				</div>
			</a>

			<!-- Main Navigation Links: Grouped into 3 intuitive Dropdowns -->
			<ul class="cinema-menu-list">
				<!-- Menu 1: Phim -->
				<li class="cinema-menu-item cinema-dropdown-parent">
					<a href="<?php echo esc_url( $home_url ); ?>#movies" class="cinema-menu-link active" id="cinema-nav-movies">
						<i class="fa-solid fa-film cinema-menu-fa"></i>
						<span>Phim</span>
						<i class="fa-solid fa-chevron-down cinema-dropdown-chevron"></i>
					</a>
					<ul class="cinema-dropdown-menu">
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $home_url ); ?>#movies" class="cinema-dropdown-link" onclick="if(window.mbsSwitchTab) { window.mbsSwitchTab('now_showing'); }">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-play"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Phim Đang Chiếu</strong>
									<span class="cinema-dropdown-sub">Suất chiếu hôm nay tại rạp</span>
								</div>
							</a>
						</li>
						<li class="cinema-dropdown-divider"></li>
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $home_url ); ?>#movies" class="cinema-dropdown-link" onclick="if(window.mbsSwitchTab) { window.mbsSwitchTab('coming_soon'); }">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-clock"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Phim Sắp Chiếu</strong>
									<span class="cinema-dropdown-sub">Bom tấn sắp ra mắt rạp</span>
								</div>
							</a>
						</li>
					</ul>
				</li>

				<!-- Menu 2: Rạp & Ưu Đãi -->
				<li class="cinema-menu-item cinema-dropdown-parent">
					<a href="<?php echo esc_url( $home_url ); ?>#cinemas" class="cinema-menu-link">
						<i class="fa-solid fa-city cinema-menu-fa"></i>
						<span>Rạp & Ưu Đãi</span>
						<i class="fa-solid fa-chevron-down cinema-dropdown-chevron"></i>
					</a>
					<ul class="cinema-dropdown-menu">
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $home_url ); ?>#cinemas" class="cinema-dropdown-link">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-building"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Hệ Thống Cụm Rạp</strong>
									<span class="cinema-dropdown-sub">Danh sách rạp chiếu hiện đại</span>
								</div>
							</a>
						</li>
						<li class="cinema-dropdown-divider"></li>
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $home_url ); ?>#promotions" class="cinema-dropdown-link">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-tag"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Khuyến Mãi & Combo</strong>
									<span class="cinema-dropdown-sub">Ưu đãi vé & bắp nước</span>
								</div>
							</a>
						</li>
					</ul>
				</li>

				<!-- Menu 3: Dịch Vụ Vé -->
				<li class="cinema-menu-item cinema-dropdown-parent">
					<a href="<?php echo esc_url( $ve_cua_toi ); ?>" class="cinema-menu-link">
						<i class="fa-solid fa-ticket cinema-menu-fa"></i>
						<span>Dịch Vụ Vé</span>
						<i class="fa-solid fa-chevron-down cinema-dropdown-chevron"></i>
					</a>
					<ul class="cinema-dropdown-menu">
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $ve_cua_toi ); ?>" class="cinema-dropdown-link">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-receipt"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Vé Của Tôi</strong>
									<span class="cinema-dropdown-sub">Lịch sử đặt vé & thanh toán</span>
								</div>
							</a>
						</li>
						<li class="cinema-dropdown-divider"></li>
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $ve_cua_toi ); ?>#lookup" class="cinema-dropdown-link">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Tra Cứu Mã Vé</strong>
									<span class="cinema-dropdown-sub">Kiểm tra thông tin vé nhanh</span>
								</div>
							</a>
						</li>
						<li class="cinema-dropdown-divider"></li>
						<li class="cinema-dropdown-item">
							<a href="<?php echo esc_url( $soat_ve ); ?>" class="cinema-dropdown-link">
								<span class="cinema-dropdown-icon"><i class="fa-solid fa-qrcode"></i></span>
								<div class="cinema-dropdown-info">
									<strong class="cinema-dropdown-title">Quầy Soát Vé QR</strong>
									<span class="cinema-dropdown-sub">Dành cho nhân viên kiểm tra vé</span>
								</div>
							</a>
						</li>
					</ul>
				</li>
			</ul>

			<!-- Header Right Action (Location, User Auth, CTA) -->
			<div class="cinema-header-actions">
				<!-- Quick Location (Compact Select) -->
				<div class="cinema-location-picker">
					<i class="fa-solid fa-location-dot cinema-fa-icon cinema-primary-icon"></i>
					<select id="cinema-quick-location" class="cinema-location-select" onchange="if(window.mbsFilterByLocation) window.mbsFilterByLocation(this.value)">
						<option value="">Hà Nội</option>
						<option value="">TP.HCM</option>
						<option value="">Đà Nẵng</option>
					</select>
				</div>

				<!-- User Account / Login Button -->
				<?php if ( $is_logged ) : ?>
					<div class="cinema-user-account-badge">
						<span class="cinema-user-avatar"><i class="fa-solid fa-circle-user"></i></span>
						<span class="cinema-user-name"><strong><?php echo esc_html( $curr_user->display_name ?: $curr_user->user_login ); ?></strong></span>
						<a href="<?php echo esc_url( wp_logout_url( $home_url ) ); ?>" class="cinema-logout-btn" title="Đăng xuất">
							<i class="fa-solid fa-right-from-bracket"></i>
						</a>
					</div>
				<?php else : ?>
					<button type="button" class="cinema-topbar-auth-btn" onclick="cinemaOpenAuthModal('login')">
						<i class="fa-solid fa-circle-user cinema-fa-icon"></i>
						<span>Đăng Nhập</span>
					</button>
				<?php endif; ?>

				<!-- Fast Booking CTA -->
				<a href="<?php echo esc_url( $home_url ); ?>#movies" class="cinema-btn-booking-cta" onclick="document.getElementById('movies')?.scrollIntoView({behavior:'smooth'})">
					<i class="fa-solid fa-ticket"></i>
					<span>ĐẶT VÉ</span>
				</a>
			</div>
		</div>
	</nav>
</header>

<!-- 3. Cinema Membership Auth Modal (Login / Register / Social) -->
<div id="cinema-auth-modal" class="cinema-auth-modal">
	<div class="cinema-auth-overlay" onclick="cinemaCloseAuthModal()"></div>
	<div class="cinema-auth-dialog">
		<button type="button" class="cinema-auth-close-btn" onclick="cinemaCloseAuthModal()">
			<i class="fa-solid fa-xmark"></i>
		</button>

		<div class="cinema-auth-header">
			<div class="cinema-auth-logo">
				<i class="fa-solid fa-clapperboard"></i> CINE<strong>MAGIC</strong>
			</div>
			<p class="cinema-auth-tagline">Cổng Thành Viên & Đặt Vé Xem Phim Chuẩn Quốc Tế</p>
		</div>

		<!-- Tab Buttons -->
		<div class="cinema-auth-tabs">
			<button type="button" class="cinema-auth-tab active" id="cinema-tab-btn-login" onclick="cinemaSwitchAuthTab('login')">
				<i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập
			</button>
			<button type="button" class="cinema-auth-tab" id="cinema-tab-btn-register" onclick="cinemaSwitchAuthTab('register')">
				<i class="fa-solid fa-user-plus"></i> Đăng Ký Thành Viên
			</button>
		</div>

		<!-- Alert Box -->
		<div id="cinema-auth-alert" class="cinema-auth-alert" style="display:none;"></div>


		<!-- FORM 1: LOGIN -->
		<form id="cinema-login-form" class="cinema-auth-form active" onsubmit="cinemaSubmitLogin(event)">
			<div class="cinema-form-field">
				<label for="cinema-login-account"><i class="fa-solid fa-user"></i> Tài khoản, Email hoặc Số điện thoại</label>
				<input type="text" id="cinema-login-account" placeholder="Nhập username, email hoặc SĐT..." required />
			</div>

			<div class="cinema-form-field">
				<div class="cinema-field-label-row">
					<label for="cinema-login-password"><i class="fa-solid fa-lock"></i> Mật khẩu</label>
					<a href="#" onclick="alert('Vui lòng liên hệ Hotline 1900 6868 hoặc gửi email tới cskh@cinemagic.vn để được cấp lại mật khẩu.'); return false;" class="cinema-forgot-link">Quên mật khẩu?</a>
				</div>
				<div class="cinema-password-input-wrap">
					<input type="password" id="cinema-login-password" placeholder="Nhập mật khẩu của bạn..." required />
					<button type="button" class="cinema-toggle-pw-btn" onclick="cinemaTogglePassword('cinema-login-password', this)">
						<i class="fa-solid fa-eye"></i>
					</button>
				</div>
			</div>

			<div class="cinema-form-checkbox">
				<label>
					<input type="checkbox" id="cinema-login-remember" checked />
					<span>Ghi nhớ đăng nhập trên thiết bị này</span>
				</label>
			</div>

			<button type="submit" class="cinema-btn-auth-submit" id="cinema-login-submit-btn">
				<i class="fa-solid fa-right-to-bracket"></i> ĐĂNG NHẬP
			</button>

			<div class="cinema-auth-switch-note">
				Chưa có tài khoản? <a href="#" onclick="cinemaSwitchAuthTab('register'); return false;">Đăng ký ngay nhận vé 0Đ</a>
			</div>
		</form>

		<!-- FORM 2: REGISTER -->
		<form id="cinema-register-form" class="cinema-auth-form" onsubmit="cinemaSubmitRegister(event)" style="display:none;">
			<div class="cinema-form-row">
				<div class="cinema-form-field">
					<label for="cinema-reg-name"><i class="fa-solid fa-id-card"></i> Họ và Tên <span class="req">*</span></label>
					<input type="text" id="cinema-reg-name" placeholder="VD: Nguyễn Văn A" required />
				</div>
				<div class="cinema-form-field">
					<label for="cinema-reg-phone"><i class="fa-solid fa-phone"></i> Số Điện Thoại <span class="req">*</span></label>
					<input type="tel" id="cinema-reg-phone" placeholder="VD: 0901234567" required pattern="[0-9]{9,11}" />
				</div>
			</div>

			<div class="cinema-form-row">
				<div class="cinema-form-field">
					<label for="cinema-reg-email"><i class="fa-solid fa-envelope"></i> Địa Chỉ Email <span class="req">*</span></label>
					<input type="email" id="cinema-reg-email" placeholder="VD: example@gmail.com" required />
				</div>
				<div class="cinema-form-field">
					<label for="cinema-reg-user"><i class="fa-solid fa-user"></i> Tên Đăng Nhập</label>
					<input type="text" id="cinema-reg-user" placeholder="Tự động tạo theo email nếu để trống" />
				</div>
			</div>

			<div class="cinema-form-field">
				<label for="cinema-reg-password"><i class="fa-solid fa-lock"></i> Mật Khẩu (Tối thiểu 6 ký tự) <span class="req">*</span></label>
				<div class="cinema-password-input-wrap">
					<input type="password" id="cinema-reg-password" placeholder="Nhập mật khẩu an toàn..." required minlength="6" />
					<button type="button" class="cinema-toggle-pw-btn" onclick="cinemaTogglePassword('cinema-reg-password', this)">
						<i class="fa-solid fa-eye"></i>
					</button>
				</div>
			</div>

			<div class="cinema-form-checkbox">
				<label>
					<input type="checkbox" id="cinema-reg-agree" checked required />
					<span>Tôi đồng ý với <a href="#" style="color:var(--cinema-primary);">Điều khoản thành viên</a> & nhận ưu đãi vé 2D miễn phí</span>
				</label>
			</div>

			<button type="submit" class="cinema-btn-auth-submit" id="cinema-reg-submit-btn">
				<i class="fa-solid fa-gift"></i> ĐĂNG KÝ THÀNH VIÊN (TẶNG VÉ 2D)
			</button>

			<div class="cinema-auth-switch-note">
				Đã có tài khoản? <a href="#" onclick="cinemaSwitchAuthTab('login'); return false;">Đăng nhập ngay</a>
			</div>
		</form>
	</div>
</div>

<script>
window.cinemaAuthRestUrl = '<?php echo esc_url_raw( rest_url( 'movie-booking/v1/auth/' ) ); ?>';

function cinemaOpenAuthModal(tab) {
	const modal = document.getElementById('cinema-auth-modal');
	if (!modal) return;
	modal.classList.add('active');
	cinemaSwitchAuthTab(tab || 'login');
}

function cinemaCloseAuthModal() {
	const modal = document.getElementById('cinema-auth-modal');
	if (modal) modal.classList.remove('active');
}

function cinemaSwitchAuthTab(tab) {
	const btnLogin = document.getElementById('cinema-tab-btn-login');
	const btnReg = document.getElementById('cinema-tab-btn-register');
	const formLogin = document.getElementById('cinema-login-form');
	const formReg = document.getElementById('cinema-register-form');
	const alertBox = document.getElementById('cinema-auth-alert');
	if (alertBox) alertBox.style.display = 'none';

	if (tab === 'register') {
		btnLogin?.classList.remove('active');
		btnReg?.classList.add('active');
		if (formLogin) formLogin.style.display = 'none';
		if (formReg) { formReg.style.display = 'block'; formReg.classList.add('active'); }
	} else {
		btnReg?.classList.remove('active');
		btnLogin?.classList.add('active');
		if (formReg) formReg.style.display = 'none';
		if (formLogin) { formLogin.style.display = 'block'; formLogin.classList.add('active'); }
	}
}

function cinemaTogglePassword(inputId, btn) {
	const input = document.getElementById(inputId);
	if (!input) return;
	if (input.type === 'password') {
		input.type = 'text';
		btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
	} else {
		input.type = 'password';
		btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
	}
}

function cinemaShowAuthAlert(msg, isSuccess) {
	const alertBox = document.getElementById('cinema-auth-alert');
	if (!alertBox) return;
	alertBox.className = 'cinema-auth-alert ' + (isSuccess ? 'alert-success' : 'alert-error');
	alertBox.innerHTML = (isSuccess ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') + msg;
	alertBox.style.display = 'block';
}

function cinemaSubmitLogin(e) {
	e.preventDefault();
	const account = document.getElementById('cinema-login-account').value.trim();
	const password = document.getElementById('cinema-login-password').value;
	const remember = document.getElementById('cinema-login-remember').checked;
	const submitBtn = document.getElementById('cinema-login-submit-btn');

	if (!account || !password) {
		cinemaShowAuthAlert('Vui lòng nhập tài khoản (Email, SĐT) và mật khẩu.', false);
		return;
	}

	submitBtn.disabled = true;
	submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang xác thực...';

	fetch(window.cinemaAuthRestUrl + 'login', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ account: account, password: password, remember: remember })
	})
	.then(res => res.json().then(data => ({ status: res.status, data: data })))
	.then(res => {
		submitBtn.disabled = false;
		submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> ĐĂNG NHẬP';

		if (res.status === 200 && res.data.success) {
			cinemaShowAuthAlert(res.data.message || 'Đăng nhập thành công!', true);
			setTimeout(() => { window.location.reload(); }, 900);
		} else {
			cinemaShowAuthAlert(res.data.message || 'Đăng nhập thất bại. Vui lòng kiểm tra lại.', false);
		}
	})
	.catch(err => {
		submitBtn.disabled = false;
		submitBtn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> ĐĂNG NHẬP';
		cinemaShowAuthAlert('Không thể kết nối đến máy chủ. Vui lòng thử lại.', false);
	});
}

function cinemaSubmitRegister(e) {
	e.preventDefault();
	const name = document.getElementById('cinema-reg-name').value.trim();
	const phone = document.getElementById('cinema-reg-phone').value.trim();
	const email = document.getElementById('cinema-reg-email').value.trim();
	const username = document.getElementById('cinema-reg-user').value.trim();
	const pass = document.getElementById('cinema-reg-password').value;
	const submitBtn = document.getElementById('cinema-reg-submit-btn');

	if (!name || !phone || !email || !pass) {
		cinemaShowAuthAlert('Vui lòng điền đầy đủ các mục có dấu (*).', false);
		return;
	}

	submitBtn.disabled = true;
	submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Đang tạo tài khoản...';

	fetch(window.cinemaAuthRestUrl + 'register', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ full_name: name, phone: phone, email: email, username: username, password: pass })
	})
	.then(res => res.json().then(data => ({ status: res.status, data: data })))
	.then(res => {
		submitBtn.disabled = false;
		submitBtn.innerHTML = '<i class="fa-solid fa-gift"></i> ĐĂNG KÝ THÀNH VIÊN (TẶNG VÉ 2D)';

		if (res.status === 200 && res.data.success) {
			cinemaShowAuthAlert(res.data.message || 'Đăng ký thành công!', true);
			setTimeout(() => { window.location.reload(); }, 1200);
		} else {
			cinemaShowAuthAlert(res.data.message || 'Đăng ký thất bại. Vui lòng thử lại.', false);
		}
	})
	.catch(err => {
		submitBtn.disabled = false;
		submitBtn.innerHTML = '<i class="fa-solid fa-gift"></i> ĐĂNG KÝ THÀNH VIÊN (TẶNG VÉ 2D)';
		cinemaShowAuthAlert('Lỗi kết nối máy chủ.', false);
	});
}

window.mbsFilterByLocation = function(cinemaId) {
	const filterDropdown = document.getElementById('mbs-filter-cinema');
	if (filterDropdown) {
		if (cinemaId) {
			filterDropdown.value = cinemaId;
			$(filterDropdown).trigger('change');
		}
		const moviesEl = document.getElementById('movies');
		if (moviesEl) moviesEl.scrollIntoView({ behavior: 'smooth' });
	} else if (cinemaId) {
		window.location.href = '<?php echo esc_url( $home_url ); ?>#movies';
	}
};
</script>
