<?php
/**
 * Fired during plugin activation
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Activator {

	/**
	 * Run activator tasks
	 */
	public static function activate() {
		self::create_custom_tables();
		self::create_user_roles();
		self::create_default_pages();
		self::init_default_settings();
		flush_rewrite_rules();
	}

	/**
	 * Create Custom Database Tables via dbDelta
	 */
	public static function create_custom_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// 1. Rooms Table (Phòng chiếu)
		$table_rooms = $wpdb->prefix . 'booking_rooms';
		$sql_rooms = "CREATE TABLE $table_rooms (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cinema_id bigint(20) unsigned NOT NULL,
			room_name varchar(100) NOT NULL,
			room_type varchar(50) DEFAULT 'Standard',
			total_rows int(11) NOT NULL DEFAULT 8,
			total_cols int(11) NOT NULL DEFAULT 10,
			seat_layout longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY cinema_id (cinema_id)
		) $charset_collate;";
		dbDelta( $sql_rooms );

		// 2. Seats Table (Danh sách ghế)
		$table_seats = $wpdb->prefix . 'booking_seats';
		$sql_seats = "CREATE TABLE $table_seats (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			room_id bigint(20) unsigned NOT NULL,
			seat_row varchar(5) NOT NULL,
			seat_number int(11) NOT NULL,
			seat_type varchar(20) NOT NULL DEFAULT 'regular',
			seat_price_modifier decimal(10,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY room_id (room_id),
			KEY row_number (seat_row, seat_number)
		) $charset_collate;";
		dbDelta( $sql_seats );

		// 3. Seat Locks Table (Khóa ghế tạm thời chống race condition)
		$table_locks = $wpdb->prefix . 'booking_seat_locks';
		$sql_locks = "CREATE TABLE $table_locks (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			showtime_id bigint(20) unsigned NOT NULL,
			seat_id bigint(20) unsigned NOT NULL,
			session_token varchar(64) NOT NULL,
			user_id bigint(20) unsigned DEFAULT 0,
			locked_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY showtime_seat (showtime_id, seat_id),
			KEY session_token (session_token),
			KEY expires_at (expires_at)
		) $charset_collate;";
		dbDelta( $sql_locks );

		// 4. Orders Table (Đơn đặt vé)
		$table_orders = $wpdb->prefix . 'booking_orders';
		$sql_orders = "CREATE TABLE $table_orders (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_code varchar(50) NOT NULL,
			user_id bigint(20) unsigned DEFAULT 0,
			customer_name varchar(150) NOT NULL,
			customer_email varchar(150) NOT NULL,
			customer_phone varchar(30) NOT NULL,
			total_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			discount_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			final_amount decimal(12,2) NOT NULL DEFAULT 0.00,
			combos_json longtext DEFAULT NULL,
			coupon_code varchar(50) DEFAULT NULL,
			payment_method varchar(30) NOT NULL DEFAULT 'sandbox',
			payment_status varchar(30) NOT NULL DEFAULT 'pending',
			transaction_id varchar(100) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY order_code (order_code),
			KEY user_id (user_id),
			KEY payment_status (payment_status)
		) $charset_collate;";
		dbDelta( $sql_orders );

		// 5. Tickets Table (Vé chi tiết)
		$table_tickets = $wpdb->prefix . 'booking_tickets';
		$sql_tickets = "CREATE TABLE $table_tickets (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			showtime_id bigint(20) unsigned NOT NULL,
			seat_id bigint(20) unsigned NOT NULL,
			seat_label varchar(20) NOT NULL,
			seat_type varchar(20) NOT NULL DEFAULT 'regular',
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			ticket_code varchar(64) NOT NULL,
			qr_code_hash varchar(128) NOT NULL,
			checkin_status varchar(20) NOT NULL DEFAULT 'pending',
			checkin_time datetime DEFAULT NULL,
			checkin_by bigint(20) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY ticket_code (ticket_code),
			UNIQUE KEY showtime_seat (showtime_id, seat_id),
			KEY order_id (order_id),
			KEY showtime_id (showtime_id),
			KEY qr_code_hash (qr_code_hash),
			KEY checkin_status (checkin_status)
		) $charset_collate;";
		dbDelta( $sql_tickets );

		// 6. Coupons Table (Mã giảm giá)
		$table_coupons = $wpdb->prefix . 'booking_coupons';
		$sql_coupons = "CREATE TABLE $table_coupons (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(50) NOT NULL,
			discount_type varchar(20) NOT NULL DEFAULT 'percentage',
			discount_value decimal(10,2) NOT NULL DEFAULT 0.00,
			min_order_amount decimal(10,2) NOT NULL DEFAULT 0.00,
			usage_limit int(11) NOT NULL DEFAULT 100,
			used_count int(11) NOT NULL DEFAULT 0,
			expiry_date date DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code)
		) $charset_collate;";
		dbDelta( $sql_coupons );
	}

	/**
	 * Create Custom User Roles & Capabilities
	 */
	public static function create_user_roles() {
		// Administrator capabilities
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'mbs_manage_system' );
			$admin->add_cap( 'mbs_view_reports' );
			$admin->add_cap( 'mbs_checkin_tickets' );
		}

		// Cinema Staff: Only checkin tickets
		add_role( 'cinema_staff', __( 'Nhân Viên Rạp Phim', 'movie-booking-system' ), array(
			'read'                 => true,
			'mbs_checkin_tickets'  => true,
		) );

		// Cinema Accountant: View reports and orders
		add_role( 'cinema_accountant', __( 'Kế Toán Rạp Phim', 'movie-booking-system' ), array(
			'read'              => true,
			'mbs_view_reports'  => true,
		) );
	}

	/**
	 * Create default frontend pages
	 */
	public static function create_default_pages() {
		$pages = array(
			array(
				'title'   => __( 'Đặt Vé Xem Phim', 'movie-booking-system' ),
				'slug'    => 'dat-ve',
				'content' => '[movie_booking_app]',
			),
			array(
				'title'   => __( 'Vé Của Tôi', 'movie-booking-system' ),
				'slug'    => 've-cua-toi',
				'content' => '[movie_booking_account]',
			),
			array(
				'title'   => __( 'Soát Vé Quầy', 'movie-booking-system' ),
				'slug'    => 'soat-ve',
				'content' => '[movie_booking_checkin]',
			),
		);

		foreach ( $pages as $page ) {
			$existing = get_page_by_path( $page['slug'] );
			if ( ! $existing ) {
				$page_id = wp_insert_post( array(
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
				) );
			}
		}
	}

	/**
	 * Initialize Default Settings
	 */
	public static function init_default_settings() {
		if ( ! get_option( 'mbs_settings' ) ) {
			$default_settings = array(
				'seat_lock_duration'     => 10, // minutes
				'cancel_allowed_before'  => 120, // minutes
				'currency_symbol'        => 'đ',
				'points_rate'            => 10, // 10 points per 100,000đ
				'vnpay_sandbox'          => 1,
				'vnpay_tmn_code'         => 'DEMOVNPAY',
				'vnpay_hash_secret'      => 'RAOSecretKey123456789DemoVNPaySandbox',
				'vnpay_url'              => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
				'momo_sandbox'           => 1,
				'momo_partner_code'      => 'MOMODEMO123',
				'momo_access_key'        => 'DemoAccessKeyMomo',
				'momo_secret_key'        => 'DemoSecretKeyMomo',
				'sms_api_key'            => 'DEMO_SMS_SPEEDSMS_123',
				'email_sender_name'      => 'Cinema Booking System',
				'email_sender_address'   => 'no-reply@cinemabooking.local',
			);
			update_option( 'mbs_settings', $default_settings );
		}
	}
}
