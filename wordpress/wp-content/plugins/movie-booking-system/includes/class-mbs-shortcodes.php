<?php
/**
 * Frontend Shortcodes Registration
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Shortcodes {

	/**
	 * Init shortcodes
	 */
	public static function init() {
		add_shortcode( 'movie_booking_app', array( __CLASS__, 'render_booking_app' ) );
		add_shortcode( 'movie_booking_account', array( __CLASS__, 'render_user_account' ) );
		add_shortcode( 'movie_booking_checkin', array( __CLASS__, 'render_checkin' ) );
	}

	/**
	 * Render Main Cinema Booking Single-Page App
	 */
	public static function render_booking_app( $atts ) {
		ob_start();
		?>
		<div id="mbs-app" class="mbs-theme-dark">
			<!-- Header / Topbar Navigation -->
			<nav class="mbs-nav">
				<div class="mbs-nav-brand">
					<span class="mbs-logo-icon"><i class="fa-solid fa-clapperboard"></i></span>
					<span class="mbs-logo-text">CINE<strong>MAGIC</strong></span>
				</div>
				<div class="mbs-nav-links">
					<a href="#movies" class="mbs-nav-item active" id="mbs-nav-home">Phim Đang Chiếu</a>
					<a href="<?php echo esc_url( home_url( '/ve-cua-toi/' ) ); ?>" class="mbs-nav-item">Vé Của Tôi</a>
					<?php if ( current_user_can( 'mbs_checkin_tickets' ) || current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( home_url( '/soat-ve/' ) ); ?>" class="mbs-nav-item mbs-badge-staff">Quầy Soát Vé</a>
					<?php endif; ?>
				</div>
			</nav>

			<?php
			$hot_movies_posts = get_posts( array(
				'post_type'      => 'movie',
				'posts_per_page' => 5,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array( 'key' => '_mbs_is_hot', 'value' => '1', 'compare' => '=' ),
				),
				'orderby'        => 'ID',
				'order'          => 'ASC',
			) );

			if ( empty( $hot_movies_posts ) ) {
				$hot_movies_posts = get_posts( array(
					'post_type'      => 'movie',
					'posts_per_page' => 5,
					'post_status'    => 'publish',
				) );
			}
			?>
			<!-- Hero Banner Carousel (Multiple Hot Movies) -->
			<section class="mbs-hero-banner" id="mbs-hero-banner">
				<div class="mbs-banner-slider" id="mbs-banner-slider">
					<?php foreach ( $hot_movies_posts as $idx => $hmp ) : 
						$h_rating   = get_post_meta( $hmp->ID, '_mbs_rating_score', true ) ?: '8.8';
						$h_duration = get_post_meta( $hmp->ID, '_mbs_duration', true ) ?: '120';
						$h_age      = get_post_meta( $hmp->ID, '_mbs_age_rating', true ) ?: 'T16';
						$h_format   = get_post_meta( $hmp->ID, '_mbs_format', true ) ?: 'IMAX 2D';
						$h_tag      = get_post_meta( $hmp->ID, '_mbs_hero_tag', true ) ?: 'SIÊU BOM TẤN ĐANG CHIẾU';
						$h_trailer  = get_post_meta( $hmp->ID, '_mbs_trailer_url', true ) ?: 'https://www.youtube.com/watch?v=Way9Dexny3w';
						$h_backdrop = get_post_meta( $hmp->ID, '_mbs_backdrop_url', true ) ?: get_post_meta( $hmp->ID, '_mbs_poster_custom', true );
						$h_desc     = wp_strip_all_tags( $hmp->post_excerpt ?: wp_trim_words( $hmp->post_content, 32 ) );
					?>
						<div class="mbs-banner-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo $idx; ?>" style="background-image: linear-gradient(to right, rgba(11,15,25,0.95) 20%, rgba(11,15,25,0.4) 70%, rgba(11,15,25,0.95)), url('<?php echo esc_url( $h_backdrop ); ?>');">
							<div class="mbs-banner-content">
								<span class="mbs-badge-hot"><i class="fa-solid fa-fire"></i> <?php echo esc_html( $h_tag ); ?></span>
								<h1 class="mbs-banner-title"><?php echo esc_html( $hmp->post_title ); ?></h1>
								<div class="mbs-banner-meta">
									<span class="mbs-rating"><i class="fa-solid fa-star"></i> <?php echo esc_html( $h_rating ); ?>/10</span>
									<span class="mbs-duration"><i class="fa-solid fa-clock"></i> <?php echo esc_html( $h_duration ); ?> phút</span>
									<span class="mbs-age"><?php echo esc_html( $h_age ); ?></span>
									<span class="mbs-format"><?php echo esc_html( $h_format ); ?></span>
								</div>
								<p class="mbs-banner-desc"><?php echo esc_html( $h_desc ); ?></p>
								<div class="mbs-banner-actions">
									<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-large" onclick="mbsOpenMovieQuick(<?php echo $hmp->ID; ?>)">
										<i class="fa-solid fa-ticket"></i> Đặt Vé Ngay
									</button>
									<button type="button" class="mbs-btn mbs-btn-secondary mbs-btn-large" onclick="mbsPlayTrailer('<?php echo esc_url( $h_trailer ); ?>')">
										<i class="fa-solid fa-play"></i> Xem Trailer
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Slider Navigation Arrows -->
				<button type="button" class="mbs-slider-arrow mbs-slider-prev" onclick="mbsPrevSlide()" aria-label="Phim trước">
					<i class="fa-solid fa-chevron-left"></i>
				</button>
				<button type="button" class="mbs-slider-arrow mbs-slider-next" onclick="mbsNextSlide()" aria-label="Phim tiếp">
					<i class="fa-solid fa-chevron-right"></i>
				</button>

				<!-- Slider Dots Indicator -->
				<div class="mbs-slider-dots" id="mbs-slider-dots">
					<?php foreach ( $hot_movies_posts as $idx => $hmp ) : ?>
						<button type="button" class="mbs-slider-dot <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="mbsGoToSlide(<?php echo $idx; ?>)" aria-label="Slide <?php echo $idx + 1; ?>"></button>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- Search & Filter Bar -->
			<section class="mbs-filter-section">
				<div class="mbs-filter-container">
					<div class="mbs-search-box">
						<span class="mbs-search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
						<input type="text" id="mbs-search-input" placeholder="Tìm tên phim, đạo diễn, diễn viên..." />
					</div>
					<div class="mbs-select-group">
						<select id="mbs-filter-cinema" class="mbs-select">
							<option value="">Tất cả rạp chiếu</option>
						</select>
						<select id="mbs-filter-genre" class="mbs-select">
							<option value="">Tất cả thể loại</option>
						</select>
						<select id="mbs-filter-status" class="mbs-select">
							<option value="now_showing">Phim Đang Chiếu</option>
							<option value="coming_soon">Phim Sắp Chiếu</option>
							<option value="all">Toàn bộ phim</option>
						</select>
					</div>
				</div>
			</section>

			<!-- Movies Grid -->
			<section class="mbs-movies-section" id="movies">
				<div class="mbs-section-header">
					<h2 class="mbs-section-title">Danh Sách Phim</h2>
					<div class="mbs-tab-buttons">
						<button class="mbs-tab-btn active" data-tab="now_showing">Đang Chiếu</button>
						<button class="mbs-tab-btn" data-tab="coming_soon">Sắp Chiếu</button>
					</div>
				</div>

				<div class="mbs-movies-grid" id="mbs-movies-grid">
					<div class="mbs-loading-state">
						<div class="mbs-spinner"></div>
						<p>Đang tải lịch chiếu từ hệ thống...</p>
					</div>
				</div>
			</section>

			<!-- Promotions & Combos Section -->
			<section class="mbs-promotions-section" id="promotions">
				<div class="mbs-section-header">
					<div>
						<h2 class="mbs-section-title"><i class="fa-solid fa-tag cinema-fa-icon"></i> Khuyến Mãi & Combo Ưu Đãi</h2>
						<p class="mbs-section-subtitle">Săn voucher giảm giá bom tấn & combo bắp nước hảo hạng từ hệ thống Cinemagic</p>
					</div>
					<div class="mbs-tab-buttons" id="mbs-promo-tabs">
						<button class="mbs-tab-btn active" data-promo-tab="all">Tất Cả</button>
						<button class="mbs-tab-btn" data-promo-tab="voucher">Voucher Giảm Giá</button>
						<button class="mbs-tab-btn" data-promo-tab="combo">Combo Bắp Nước</button>
					</div>
				</div>

				<div class="mbs-promotions-grid" id="mbs-promotions-grid">
					<div class="mbs-loading-state">
						<div class="mbs-spinner"></div>
						<p>Đang tải ưu đãi khuyến mãi...</p>
					</div>
				</div>
			</section>

			<!-- Cinemas System Section -->
			<section class="mbs-cinemas-section" id="cinemas">
				<div class="mbs-section-header">
					<div>
						<h2 class="mbs-section-title"><i class="fa-solid fa-city cinema-fa-icon"></i> Hệ Thống Cụm Rạp Toàn Quốc</h2>
						<p class="mbs-section-subtitle">Trải nghiệm màn hình IMAX Laser, âm thanh Dolby Atmos và phòng chiếu Gold Class thượng lưu</p>
					</div>
					<div class="mbs-tab-buttons" id="mbs-cinema-city-tabs">
						<button class="mbs-tab-btn active" data-city="all">Tất Cả Rạp</button>
						<button class="mbs-tab-btn" data-city="Hà Nội">Hà Nội</button>
						<button class="mbs-tab-btn" data-city="Hồ Chí Minh">TP. Hồ Chí Minh</button>
						<button class="mbs-tab-btn" data-city="Đà Nẵng">Đà Nẵng</button>
					</div>
				</div>

				<div class="mbs-cinemas-grid" id="mbs-cinemas-grid">
					<div class="mbs-loading-state">
						<div class="mbs-spinner"></div>
						<p>Đang tải danh sách cụm rạp...</p>
					</div>
				</div>
			</section>

			<!-- MODAL: Movie Detail & Showtime Selection -->
			<div class="mbs-modal" id="mbs-movie-modal">
				<div class="mbs-modal-overlay" onclick="mbsCloseModal('mbs-movie-modal')"></div>
				<div class="mbs-modal-dialog mbs-modal-lg">
					<button class="mbs-modal-close" onclick="mbsCloseModal('mbs-movie-modal')">×</button>
					<div class="mbs-modal-content" id="mbs-movie-modal-body">
						<!-- Injected dynamically via JS -->
					</div>
				</div>
			</div>

			<!-- MODAL: Trailer YouTube Pop-up -->
			<div class="mbs-modal" id="mbs-trailer-modal">
				<div class="mbs-modal-overlay" onclick="mbsCloseTrailerModal()"></div>
				<div class="mbs-modal-dialog mbs-modal-video">
					<button class="mbs-modal-close" onclick="mbsCloseTrailerModal()">×</button>
					<div class="mbs-video-wrapper" id="mbs-trailer-container"></div>
				</div>
			</div>

			<!-- MODAL: Seat Map Selection (Sơ đồ ghế) -->
			<div class="mbs-modal" id="mbs-seat-modal">
				<div class="mbs-modal-overlay"></div>
				<div class="mbs-modal-dialog mbs-modal-xl">
					<button class="mbs-modal-close" onclick="mbsCloseSeatModal()">×</button>
					<div class="mbs-seat-booking-wrapper">
						<!-- Left: Seat Map Layout -->
						<div class="mbs-seat-map-col">
							<div class="mbs-seat-header">
								<div class="mbs-seat-info-meta">
									<h3 id="mbs-seat-movie-title">Tên Phim</h3>
									<p id="mbs-seat-showtime-meta">Rạp • Giờ chiếu • Phòng</p>
								</div>
								<!-- Live Countdown Timer -->
								<div class="mbs-timer-pill" id="mbs-lock-timer" style="display:none;">
									<span>⏳ Giữ chỗ:</span>
									<strong id="mbs-timer-digits">10:00</strong>
								</div>
							</div>

							<!-- 3D Curved Cinema Screen -->
							<div class="mbs-screen-container">
								<div class="mbs-screen-curve"></div>
								<span class="mbs-screen-label">MÀN HÌNH CHIẾU PHIM (SCREEN)</span>
							</div>

							<!-- Interactive Seat Grid -->
							<div class="mbs-seats-canvas" id="mbs-seats-canvas">
								<!-- Injected dynamically -->
							</div>

							<!-- Legend -->
							<div class="mbs-seat-legend">
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-regular"></span> Ghế Thường</div>
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-vip"></span> Ghế VIP (+20k)</div>
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-couple"></span> Ghế Đôi Couple (+40k)</div>
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-selected"></span> Đang chọn</div>
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-locked"></span> Đang giữ chỗ</div>
								<div class="mbs-legend-item"><span class="mbs-seat-sample seat-booked"></span> Đã đặt</div>
							</div>
						</div>

						<!-- Right: Dynamic Cart & Concession Selection -->
						<div class="mbs-seat-summary-col">
							<h4 class="mbs-summary-title">Thông Tin Đặt Vé</h4>

							<div class="mbs-summary-card">
								<div class="mbs-summary-row">
									<span>Ghế đang chọn:</span>
									<strong id="mbs-selected-seats-list" class="mbs-highlight-text">Chưa chọn ghế</strong>
								</div>
								<div class="mbs-summary-row">
									<span>Tiền vé:</span>
									<strong id="mbs-tickets-total-amount">0đ</strong>
								</div>
							</div>

							<!-- Concession Combos -->
							<div class="mbs-combos-section">
								<h5 class="mbs-sub-title"><i class="fa-solid fa-film"></i> Combo Bắp Nước Ưu Đãi</h5>
								<div class="mbs-combos-list" id="mbs-combos-list">
									<!-- Injected dynamically -->
								</div>
							</div>

							<!-- Coupon Input -->
							<div class="mbs-coupon-box">
								<input type="text" id="mbs-coupon-input" placeholder="Nhập mã giảm (VD: CINEMA50)" />
								<button type="button" id="mbs-apply-coupon-btn" class="mbs-btn mbs-btn-sm" onclick="mbsApplyCoupon()">Áp Dụng</button>
							</div>
							<div id="mbs-coupon-message" class="mbs-alert-msg" style="display:none;"></div>

							<!-- Final Calculation -->
							<div class="mbs-checkout-summary">
								<div class="mbs-summary-row">
									<span>Tổng phụ:</span>
									<span id="mbs-calc-subtotal">0đ</span>
								</div>
								<div class="mbs-summary-row mbs-discount-row" id="mbs-discount-display" style="display:none;">
									<span>Giảm giá:</span>
									<span id="mbs-calc-discount">-0đ</span>
								</div>
								<div class="mbs-summary-row mbs-total-row">
									<span>Tổng thanh toán:</span>
									<span id="mbs-calc-final" class="mbs-grand-total">0đ</span>
								</div>
							</div>

							<button class="mbs-btn mbs-btn-primary mbs-btn-block" id="mbs-proceed-checkout-btn" onclick="mbsOpenCheckoutForm()" disabled>
								Tiến Hành Thanh Toán
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- MODAL: Checkout Customer Info & Payment Gateway -->
			<div class="mbs-modal" id="mbs-checkout-modal">
				<div class="mbs-modal-overlay" onclick="mbsCloseModal('mbs-checkout-modal')"></div>
				<div class="mbs-modal-dialog">
					<button class="mbs-modal-close" onclick="mbsCloseModal('mbs-checkout-modal')">×</button>
					<div class="mbs-modal-content">
						<h3 class="mbs-modal-heading">Xác Nhận & Thanh Toán</h3>
						
						<form id="mbs-checkout-form" onsubmit="mbsSubmitCheckout(event)">
							<div class="mbs-form-group">
								<label for="mbs-cust-name">Họ và Tên <span class="req">*</span></label>
								<input type="text" id="mbs-cust-name" required placeholder="Nguyễn Văn A" 
									value="<?php echo esc_attr( wp_get_current_user()->display_name ?? '' ); ?>" />
							</div>

							<div class="mbs-form-row">
								<div class="mbs-form-group">
									<label for="mbs-cust-phone">Số Điện Thoại <span class="req">*</span></label>
									<input type="tel" id="mbs-cust-phone" required placeholder="0901234567" />
								</div>
								<div class="mbs-form-group">
									<label for="mbs-cust-email">Email Nhận Vé <span class="req">*</span></label>
									<input type="email" id="mbs-cust-email" required placeholder="email@gmail.com"
										value="<?php echo esc_attr( wp_get_current_user()->user_email ?? '' ); ?>" />
								</div>
							</div>

							<div class="mbs-form-group">
								<label>Phương Thức Thanh Toán</label>
								<div class="mbs-payment-options">
									<label class="mbs-payment-card active">
										<input type="radio" name="mbs_payment_method" value="sandbox" checked />
										<div class="mbs-pay-icon"><i class="fa-solid fa-bolt"></i></div>
										<div class="mbs-pay-info">
											<strong>Sandbox Test Nhanh</strong>
											<span>Dành cho kiểm thử tức thì</span>
										</div>
									</label>

									<label class="mbs-payment-card">
										<input type="radio" name="mbs_payment_method" value="vnpay" />
										<div class="mbs-pay-icon"><i class="fa-solid fa-credit-card"></i></div>
										<div class="mbs-pay-info">
											<strong>VNPay Sandbox</strong>
											<span>Thẻ ATM / QR VNPay / Visa</span>
										</div>
									</label>

									<label class="mbs-payment-card">
										<input type="radio" name="mbs_payment_method" value="momo" />
										<div class="mbs-pay-icon"><i class="fa-solid fa-mobile-screen"></i></div>
										<div class="mbs-pay-info">
											<strong>Ví MoMo Sandbox</strong>
											<span>Quét QR MoMo ví điện tử</span>
										</div>
									</label>
								</div>
							</div>

							<div class="mbs-order-total-preview">
								<span>Tổng tiền thanh toán:</span>
								<strong id="mbs-modal-final-amount">0đ</strong>
							</div>

							<button type="submit" class="mbs-btn mbs-btn-primary mbs-btn-block mbs-btn-large" id="mbs-pay-submit-btn">
								<i class="fa-solid fa-lock"></i> Xác Nhận Đặt Vé & Thanh Toán
							</button>
						</form>
					</div>
				</div>
			</div>

			<!-- MODAL: Booking Success & Electronic Ticket With QR Code -->
			<div class="mbs-modal" id="mbs-success-modal">
				<div class="mbs-modal-overlay"></div>
				<div class="mbs-modal-dialog mbs-modal-ticket">
					<button class="mbs-modal-close" onclick="mbsCloseModal('mbs-success-modal')">×</button>
					<div class="mbs-ticket-container" id="mbs-ticket-container">
						<!-- Injected dynamically with perforated cinema ticket styling & QR code -->
					</div>
					<div class="mbs-ticket-actions">
						<button class="mbs-btn mbs-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> In Vé Xem Phim</button>
						<a href="<?php echo esc_url( home_url( '/ve-cua-toi/' ) ); ?>" class="mbs-btn mbs-btn-secondary"><i class="fa-solid fa-clock-rotate-left"></i> Xem Lịch Sử Vé</a>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render User Account Dashboard & Ticket History
	 */
	public static function render_user_account( $atts ) {
		ob_start();
		$is_logged = is_user_logged_in();
		$current_user = $is_logged ? wp_get_current_user() : null;
		$points = $is_logged ? (int) get_user_meta( $current_user->ID, '_mbs_loyalty_points', true ) : 0;
		?>
		<div id="mbs-user-dashboard" class="mbs-theme-dark mbs-dashboard-wrapper">

			<!-- 1. TRA CỨU VÉ NHANH (CHO CẢ KHÁCH VÀ THÀNH VIÊN) -->
			<section class="mbs-ticket-lookup-section" id="lookup">
				<div class="mbs-lookup-card">
					<div class="mbs-lookup-header">
						<span class="mbs-lookup-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
						<div>
							<h2 class="mbs-lookup-title">Tra Cứu Vé & Đơn Hàng</h2>
							<p class="mbs-lookup-desc">Nhập Mã vé điện tử (VD: <code>TK2609...</code>), Mã đơn hàng (VD: <code>MBS2609...</code>) hoặc Số điện thoại đặt vé để lấy lại vé và mã QR check-in.</p>
						</div>
					</div>
					<form id="mbs-ticket-lookup-form" onsubmit="mbsLookupTicket(event)" class="mbs-lookup-form">
						<div class="mbs-lookup-input-wrap">
							<i class="fa-solid fa-ticket mbs-input-icon"></i>
							<input type="text" id="mbs-lookup-query" placeholder="Nhập Mã vé (TK...), Mã đơn hàng (MBS...) hoặc SĐT đặt vé..." required />
							<button type="submit" class="mbs-btn mbs-btn-primary" id="mbs-btn-lookup">
								<i class="fa-solid fa-search"></i> Tra Cứu Ngay
							</button>
						</div>
					</form>
					<div id="mbs-lookup-result" class="mbs-lookup-result-box" style="display:none;"></div>
				</div>
			</section>

			<?php if ( ! $is_logged ) : ?>
				<!-- 2. GIAO DIỆN KHI CHƯA ĐĂNG NHẬP -->
				<div class="mbs-auth-card" style="margin-top:30px;">
					<div class="mbs-auth-icon"><i class="fa-solid fa-circle-user"></i></div>
					<h2>Tài Khoản Thành Viên Cinemagic</h2>
					<p>Đăng nhập để tự động đồng bộ tất cả vé đã mua, quản lý điểm thưởng tích lũy và đổi vé xem phim miễn phí.</p>
					<div class="mbs-auth-actions">
						<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-large" onclick="if(window.cinemaOpenAuthModal) cinemaOpenAuthModal('login'); else window.location.href='<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>';">
							<i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập
						</button>
						<button type="button" class="mbs-btn mbs-btn-secondary mbs-btn-large" onclick="if(window.cinemaOpenAuthModal) cinemaOpenAuthModal('register'); else window.location.href='<?php echo esc_url( wp_registration_url() ); ?>';">
							<i class="fa-solid fa-user-plus"></i> Đăng Ký Tài Khoản
						</button>
					</div>
				</div>
			<?php else : ?>
				<!-- 3. GIAO DIỆN THÀNH VIÊN ĐÃ ĐĂNG NHẬP -->
				<div class="mbs-dash-header" style="margin-top:30px;">
					<div class="mbs-dash-user">
						<div class="mbs-dash-avatar"><?php echo get_avatar( $current_user->ID, 64 ); ?></div>
						<div>
							<h3>Xin chào, <?php echo esc_html( $current_user->display_name ?: $current_user->user_login ); ?>!</h3>
							<p class="mbs-user-email"><?php echo esc_html( $current_user->user_email ); ?></p>
						</div>
					</div>
					<div class="mbs-points-badge">
						<span class="mbs-points-label">Điểm Tích Lũy</span>
						<span class="mbs-points-val"><i class="fa-solid fa-star"></i> <?php echo number_format( $points ); ?> điểm</span>
					</div>
				</div>

				<div class="mbs-dash-content">
					<h3 class="mbs-dash-section-title"><i class="fa-solid fa-clock-rotate-left"></i> Lịch Sử Đặt Vé Của Bạn</h3>
					<div id="mbs-user-orders-list">
						<div class="mbs-spinner"></div>
					</div>
				</div>
			<?php endif; ?>

			<!-- MODAL: Perforated Electronic Ticket with Live QR Code -->
			<div class="mbs-modal" id="mbs-success-modal">
				<div class="mbs-modal-overlay" onclick="mbsCloseModal('mbs-success-modal')"></div>
				<div class="mbs-modal-dialog mbs-modal-ticket">
					<button class="mbs-modal-close" onclick="mbsCloseModal('mbs-success-modal')"><i class="fa-solid fa-xmark"></i></button>
					<div class="mbs-perforated-ticket" id="mbs-ticket-container">
						<!-- Injected dynamically via JS -->
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Staff Check-in / QR Scanner Page
	 */
	public static function render_checkin( $atts ) {
		ob_start();
		?>
		<div id="mbs-checkin-app" class="mbs-theme-dark mbs-checkin-wrapper">
			<div class="mbs-checkin-header">
				<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
					<h2><i class="fa-solid fa-qrcode"></i> Quầy Soát Vé Rạp Chiếu Phim</h2>
					<?php if ( is_user_logged_in() ) : ?>
						<span style="background:rgba(34, 197, 94, 0.2); border:1px solid #22c55e; color:#4ade80; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600;">
							<i class="fa-solid fa-user-check"></i> Nhân viên: <?php echo esc_html( wp_get_current_user()->display_name ?: wp_get_current_user()->user_login ); ?>
						</span>
					<?php else : ?>
						<span style="background:rgba(0, 229, 255, 0.15); border:1px solid rgba(0, 229, 255, 0.4); color:#38bdf8; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600;">
							<i class="fa-solid fa-bolt"></i> Quầy soát vé tự động
						</span>
					<?php endif; ?>
				</div>
				<p>Quét mã QR Code từ điện thoại của khách hàng hoặc nhập mã vé trực tiếp để xác thực check-in.</p>
			</div>

			<div class="mbs-checkin-grid">
				<!-- Scanner Box -->
				<div class="mbs-scanner-card">
					<h3><i class="fa-solid fa-camera"></i> Camera Quét Mã QR</h3>
					<div id="mbs-qr-reader" class="mbs-qr-reader-box"></div>
					<div class="mbs-scanner-controls">
						<button id="mbs-btn-start-camera" class="mbs-btn mbs-btn-primary"><i class="fa-solid fa-play"></i> Bật Camera Quét Vé</button>
						<button id="mbs-btn-stop-camera" class="mbs-btn mbs-btn-secondary" style="display:none;"><i class="fa-solid fa-stop"></i> Dừng Camera</button>
					</div>

					<div class="mbs-manual-input-box">
						<h4>Hoặc Nhập Mã Vé Thủ Công:</h4>
						<form id="mbs-manual-checkin-form" onsubmit="mbsManualCheckin(event)">
							<div class="mbs-input-group">
								<input type="text" id="mbs-checkin-ticket-code" placeholder="Nhập mã vé (VD: TK2609...)" required />
								<button type="submit" class="mbs-btn mbs-btn-primary">Kiểm Tra</button>
							</div>
						</form>
					</div>
				</div>

				<!-- Verification Result Card -->
				<div class="mbs-result-card" id="mbs-checkin-result-card">
					<h3>Kết Quả Soát Vé</h3>
					<div id="mbs-checkin-result-placeholder" class="mbs-result-empty">
						<span><i class="fa-solid fa-clipboard-list" style="font-size:36px;color:#64748b;"></i></span>
						<p>Chưa có thông tin soát vé.<br>Vui lòng quét mã QR hoặc nhập mã vé để kiểm tra.</p>
					</div>
					<div id="mbs-checkin-result-content" style="display:none;">
						<!-- Dynamic Verification Card -->
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
