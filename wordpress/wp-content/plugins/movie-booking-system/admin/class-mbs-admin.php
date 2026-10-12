<?php
/**
 * Admin Panel & Menus Handler
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Admin {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menus' ) );
		add_action( 'admin_menu', array( __CLASS__, 'cleanup_unused_admin_menus' ), 999 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_global_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_csv_export' ) );
		add_action( 'wp_ajax_mbs_save_room_ajax', array( __CLASS__, 'ajax_save_room' ) );
		add_action( 'wp_ajax_mbs_save_coupon_ajax', array( __CLASS__, 'ajax_save_coupon' ) );
		add_action( 'wp_ajax_mbs_delete_coupon_ajax', array( __CLASS__, 'ajax_delete_coupon' ) );
		add_action( 'wp_ajax_mbs_get_report_data_ajax', array( __CLASS__, 'ajax_get_report_data' ) );
		add_action( 'wp_ajax_mbs_add_user_ajax', array( __CLASS__, 'ajax_add_user' ) );
		add_action( 'wp_ajax_mbs_get_user_ajax', array( __CLASS__, 'ajax_get_user' ) );
		add_action( 'wp_ajax_mbs_update_user_ajax', array( __CLASS__, 'ajax_update_user' ) );
		add_action( 'wp_ajax_mbs_update_user_password_ajax', array( __CLASS__, 'ajax_update_user_password' ) );
		add_action( 'wp_ajax_mbs_delete_user_ajax', array( __CLASS__, 'ajax_delete_user' ) );
	}

	/**
	 * Remove unused admin menus for cinema system (e.g. default Comments)
	 */
	public static function cleanup_unused_admin_menus() {
		remove_menu_page( 'edit-comments.php' ); // Ẩn menu Bình luận không dùng
	}

	/**
	 * Register Custom Admin Menu and Submenus
	 */
	public static function register_admin_menus() {
		$main_cap = current_user_can( 'mbs_checkin_tickets' ) && ! current_user_can( 'manage_options' ) ? 'mbs_checkin_tickets' : 'mbs_view_reports';

		// Top Level Menu
		add_menu_page(
			__( 'Quản Lý Rạp Phim', 'movie-booking-system' ),
			__( 'Quản Lý Rạp Phim', 'movie-booking-system' ),
			$main_cap,
			'mbs-dashboard',
			array( __CLASS__, 'render_reports_page' ),
			'dashicons-tickets-alt',
			25
		);

		// Submenus with FontAwesome 6 icons
		add_submenu_page(
			'mbs-dashboard',
			__( 'Thống Kê Doanh Thu', 'movie-booking-system' ),
			'<i class="fa-solid fa-chart-line"></i> ' . __( 'Thống Kê Doanh Thu', 'movie-booking-system' ),
			'mbs_view_reports',
			'mbs-dashboard',
			array( __CLASS__, 'render_reports_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Danh Sách Phim', 'movie-booking-system' ),
			'<i class="fa-solid fa-film"></i> ' . __( 'Quản Lý Phim', 'movie-booking-system' ),
			'manage_options',
			'edit.php?post_type=movie'
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Rạp & Phòng Chiếu', 'movie-booking-system' ),
			'<i class="fa-solid fa-building"></i> ' . __( 'Rạp & Phòng Chiếu', 'movie-booking-system' ),
			'manage_options',
			'mbs-cinemas',
			array( __CLASS__, 'render_cinemas_rooms_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Suất Chiếu', 'movie-booking-system' ),
			'<i class="fa-solid fa-clock"></i> ' . __( 'Quản Lý Suất Chiếu', 'movie-booking-system' ),
			'manage_options',
			'edit.php?post_type=showtime'
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Đơn Hàng & Vé', 'movie-booking-system' ),
			'<i class="fa-solid fa-ticket"></i> ' . __( 'Đơn Hàng & Vé', 'movie-booking-system' ),
			'mbs_view_reports',
			'mbs-orders',
			array( __CLASS__, 'render_orders_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Mã Giảm Giá', 'movie-booking-system' ),
			'<i class="fa-solid fa-tags"></i> ' . __( 'Mã Giảm Giá', 'movie-booking-system' ),
			'manage_options',
			'mbs-coupons',
			array( __CLASS__, 'render_coupons_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Quản Lý Người Dùng', 'movie-booking-system' ),
			'<i class="fa-solid fa-users"></i> ' . __( 'Quản Lý Người Dùng', 'movie-booking-system' ),
			'manage_options',
			'mbs-users',
			array( __CLASS__, 'render_users_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Quầy Soát Vé QR', 'movie-booking-system' ),
			'<i class="fa-solid fa-qrcode"></i> ' . __( 'Quầy Soát Vé QR', 'movie-booking-system' ),
			'mbs_checkin_tickets',
			'mbs-checkin',
			array( __CLASS__, 'render_checkin_page' )
		);

		add_submenu_page(
			'mbs-dashboard',
			__( 'Cài Đặt Hệ Thống', 'movie-booking-system' ),
			'<i class="fa-solid fa-gear"></i> ' . __( 'Cài Đặt', 'movie-booking-system' ),
			'manage_options',
			'mbs-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue Global FontAwesome 6 and Admin Styles across entire WP Admin
	 */
	public static function enqueue_global_admin_assets() {
		wp_enqueue_style(
			'font-awesome-6',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
			array(),
			'6.5.1'
		);

		$fa_css_path = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/css/admin-fontawesome.css';
		$fa_css_ver  = file_exists( $fa_css_path ) ? filemtime( $fa_css_path ) : time();
		wp_enqueue_style(
			'mbs-admin-fontawesome',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/admin-fontawesome.css',
			array( 'font-awesome-6' ),
			$fa_css_ver
		);
	}

	/**
	 * Enqueue Admin Scripts & Styles
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'mbs-' ) === false && strpos( $hook, 'movie' ) === false && strpos( $hook, 'cinema' ) === false && strpos( $hook, 'showtime' ) === false ) {
			return;
		}

		$css_ver = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/css/admin-style.css' ) ? filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/css/admin-style.css' ) : time();
		$js_ver  = file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/js/admin-script.js' ) ? filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'admin/js/admin-script.js' ) : time();

		wp_enqueue_style( 'mbs-admin-css', plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/admin-style.css', array(), $css_ver );

		// Enqueue Chart.js for reports
		wp_enqueue_script( 'mbs-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', array(), '4.4.1', true );
		// Enqueue html5-qrcode for scanner
		wp_enqueue_script( 'mbs-html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', array(), '2.3.8', true );

		wp_enqueue_script( 'mbs-admin-js', plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/admin-script.js', array( 'jquery' ), $js_ver, true );

		wp_localize_script( 'mbs-admin-js', 'mbsAdmin', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'mbs_admin_nonce' ),
			'restUrl'   => esc_url_raw( rest_url( 'movie-booking/v1/' ) ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
		) );
	}

	/**
	 * Views Rendering
	 */
	public static function render_reports_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/reports.php';
	}

	public static function render_cinemas_rooms_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/cinemas-rooms.php';
	}

	public static function render_orders_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/orders.php';
	}

	public static function render_coupons_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/coupons.php';
	}

	public static function render_checkin_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/checkin.php';
	}

	public static function render_users_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/users.php';
	}

	public static function render_settings_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/views/settings.php';
	}

	/**
	 * CSV Export for Orders
	 */
	public static function handle_csv_export() {
		if ( isset( $_GET['mbs_export_orders'] ) && current_user_can( 'mbs_view_reports' ) ) {
			check_admin_referer( 'mbs_export_orders_nonce' );

			global $wpdb;
			$table_orders = $wpdb->prefix . 'booking_orders';
			$orders = $wpdb->get_results( "SELECT * FROM $table_orders ORDER BY created_at DESC" );

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=cinema-orders-' . date( 'Y-m-d' ) . '.csv' );

			$output = fopen( 'php://output', 'w' );
			fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) ); // UTF-8 BOM

			fputcsv( $output, array( 'Mã Đơn', 'Khách Hàng', 'Email', 'SĐT', 'Tổng Tiền', 'Giảm Giá', 'Thực Thu', 'Cổng TT', 'Trạng Thái', 'Ngày Tạo' ) );

			foreach ( $orders as $o ) {
				fputcsv( $output, array(
					$o->order_code,
					$o->customer_name,
					$o->customer_email,
					$o->customer_phone,
					$o->total_amount,
					$o->discount_amount,
					$o->final_amount,
					strtoupper( $o->payment_method ),
					strtoupper( $o->payment_status ),
					$o->created_at,
				) );
			}

			fclose( $output );
			exit;
		}
	}

	/**
	 * AJAX: Save Room & Seat Grid
	 */
	public static function ajax_save_room() {
		check_ajax_referer( 'mbs_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'movie-booking-system' ) ) );
		}

		$data = array(
			'id'          => absint( $_POST['id'] ?? 0 ),
			'cinema_id'   => absint( $_POST['cinema_id'] ?? 0 ),
			'room_name'   => sanitize_text_field( $_POST['room_name'] ?? '' ),
			'room_type'   => sanitize_text_field( $_POST['room_type'] ?? 'Standard' ),
			'total_rows'  => absint( $_POST['total_rows'] ?? 8 ),
			'total_cols'  => absint( $_POST['total_cols'] ?? 10 ),
			'seat_layout' => isset( $_POST['seat_layout'] ) ? stripslashes( $_POST['seat_layout'] ) : null,
		);

		if ( empty( $data['cinema_id'] ) || empty( $data['room_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng chọn rạp và nhập tên phòng.', 'movie-booking-system' ) ) );
		}

		$room_id = MBS_DB::save_room( $data );
		wp_send_json_success( array( 'room_id' => $room_id, 'message' => __( 'Lưu phòng chiếu và tạo sơ đồ ghế thành công!', 'movie-booking-system' ) ) );
	}

	/**
	 * AJAX: Save Coupon
	 */
	public static function ajax_save_coupon() {
		check_ajax_referer( 'mbs_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'movie-booking-system' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'booking_coupons';

		$code           = strtoupper( sanitize_text_field( $_POST['code'] ?? '' ) );
		$discount_type  = sanitize_text_field( $_POST['discount_type'] ?? 'percentage' );
		$discount_value = (float) ( $_POST['discount_value'] ?? 0 );
		$min_amount     = (float) ( $_POST['min_order_amount'] ?? 0 );
		$usage_limit    = absint( $_POST['usage_limit'] ?? 100 );
		$expiry_date    = sanitize_text_field( $_POST['expiry_date'] ?? '' );

		if ( empty( $code ) || $discount_value <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập mã và giá trị giảm hợp lệ.', 'movie-booking-system' ) ) );
		}

		$wpdb->replace(
			$table,
			array(
				'code'             => $code,
				'discount_type'    => $discount_type,
				'discount_value'   => $discount_value,
				'min_order_amount' => $min_amount,
				'usage_limit'      => $usage_limit,
				'expiry_date'      => $expiry_date ?: null,
				'status'           => 'active',
			),
			array( '%s', '%s', '%f', '%f', '%d', '%s', '%s' )
		);

		wp_send_json_success( array( 'message' => __( 'Lưu mã giảm giá thành công!', 'movie-booking-system' ) ) );
	}

	/**
	 * AJAX: Delete Coupon
	 */
	public static function ajax_delete_coupon() {
		check_ajax_referer( 'mbs_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$id = absint( $_POST['id'] ?? 0 );
		$table = $wpdb->prefix . 'booking_coupons';
		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		wp_send_json_success( array( 'message' => __( 'Đã xóa mã giảm giá.', 'movie-booking-system' ) ) );
	}

	/**
	 * AJAX: Get Report Data for Chart.js
	 */
	public static function ajax_get_report_data() {
		check_ajax_referer( 'mbs_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'mbs_view_reports' ) ) {
			wp_send_json_error();
		}

		global $wpdb;
		$table_orders  = $wpdb->prefix . 'booking_orders';
		$table_tickets = $wpdb->prefix . 'booking_tickets';

		// 1. Revenue last 7 days
		$days_query = "SELECT DATE(created_at) as sale_date, SUM(final_amount) as total_rev, COUNT(id) as total_orders 
			FROM $table_orders 
			WHERE payment_status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
			GROUP BY DATE(created_at) 
			ORDER BY sale_date ASC";
		$days_data = $wpdb->get_results( $days_query );

		$labels_days = array();
		$revenue_days = array();
		$orders_days = array();

		for ( $i = 6; $i >= 0; $i-- ) {
			$d = date( 'Y-m-d', strtotime( "-$i days" ) );
			$labels_days[] = date( 'd/m', strtotime( $d ) );
			$found_rev = 0;
			$found_ord = 0;
			foreach ( $days_data as $row ) {
				if ( $row->sale_date === $d ) {
					$found_rev = (float) $row->total_rev;
					$found_ord = (int) $row->total_orders;
					break;
				}
			}
			$revenue_days[] = $found_rev;
			$orders_days[] = $found_ord;
		}

		// 2. Revenue by Movie
		$movie_query = "SELECT p.post_title as movie_name, SUM(t.price) as movie_rev
			FROM $table_tickets t
			JOIN {$wpdb->postmeta} pm ON pm.post_id = t.showtime_id AND pm.meta_key = '_mbs_movie_id'
			JOIN {$wpdb->posts} p ON p.ID = pm.meta_value
			GROUP BY p.ID
			ORDER BY movie_rev DESC LIMIT 5";
		$movie_data = $wpdb->get_results( $movie_query );

		$movie_labels = array();
		$movie_revs = array();
		foreach ( $movie_data as $m ) {
			$movie_labels[] = $m->movie_name;
			$movie_revs[] = (float) $m->movie_rev;
		}

		// Fallback sample if no sales yet
		if ( empty( $movie_labels ) ) {
			$movie_labels = array( 'Dune: Part Two', 'Avatar 2', 'Lật Mặt 7', 'Mai', 'Kung Fu Panda 4' );
			$movie_revs = array( 1450000, 980000, 860000, 720000, 450000 );
		}

		// 3. Totals
		$total_rev = $wpdb->get_var( "SELECT SUM(final_amount) FROM $table_orders WHERE payment_status = 'paid'" ) ?: 0;
		$total_tickets_sold = $wpdb->get_var( "SELECT COUNT(*) FROM $table_tickets" ) ?: 0;
		$total_orders_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_orders WHERE payment_status = 'paid'" ) ?: 0;

		wp_send_json_success( array(
			'summary' => array(
				'total_revenue'      => number_format( $total_rev, 0, ',', '.' ) . 'đ',
				'total_tickets'      => number_format( $total_tickets_sold ),
				'total_orders'       => number_format( $total_orders_count ),
				'avg_order_value'    => ( $total_orders_count > 0 ) ? number_format( $total_rev / $total_orders_count, 0, ',', '.' ) . 'đ' : '0đ',
			),
			'days' => array(
				'labels'  => $labels_days,
				'revenue' => $revenue_days,
				'orders'  => $orders_days,
			),
			'movies' => array(
				'labels'  => $movie_labels,
				'revenue' => $movie_revs,
			),
		) );
	}

	/* -------------------------------------------------------------
	 * User Management AJAX Endpoints
	 * ------------------------------------------------------------- */
	public static function ajax_add_user() {
		check_ajax_referer( 'mbs_admin_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'movie-booking-system' ) ) );
		}

		$username     = sanitize_user( $_POST['username'] ?? '' );
		$email        = sanitize_email( $_POST['email'] ?? '' );
		$password     = sanitize_text_field( $_POST['password'] ?? '' );
		$display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
		$phone        = sanitize_text_field( $_POST['phone'] ?? '' );
		$role         = sanitize_text_field( $_POST['role'] ?? 'subscriber' );

		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng nhập đầy đủ tên đăng nhập, email và mật khẩu.', 'movie-booking-system' ) ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Địa chỉ email không hợp lệ.', 'movie-booking-system' ) ) );
		}

		if ( username_exists( $username ) ) {
			wp_send_json_error( array( 'message' => __( 'Tên đăng nhập này đã tồn tại.', 'movie-booking-system' ) ) );
		}

		if ( email_exists( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Địa chỉ email này đã được sử dụng.', 'movie-booking-system' ) ) );
		}

		$allowed_roles = array( 'subscriber', 'cinema_staff', 'cinema_accountant', 'administrator' );
		if ( ! in_array( $role, $allowed_roles, true ) ) {
			$role = 'subscriber';
		}

		$user_id = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		$user = new WP_User( $user_id );
		$user->set_role( $role );

		if ( ! empty( $display_name ) ) {
			wp_update_user( array(
				'ID'           => $user_id,
				'display_name' => $display_name,
				'nickname'     => $display_name,
			) );
		}

		if ( ! empty( $phone ) ) {
			update_user_meta( $user_id, 'phone', $phone );
			update_user_meta( $user_id, 'billing_phone', $phone );
		}

		wp_send_json_success( array(
			'message' => __( 'Thêm người dùng mới thành công!', 'movie-booking-system' ),
			'user_id' => $user_id,
		) );
	}

	public static function ajax_get_user() {
		check_ajax_referer( 'mbs_admin_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'movie-booking-system' ) ) );
		}

		$user_id = intval( $_POST['user_id'] ?? 0 );
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy người dùng.', 'movie-booking-system' ) ) );
		}

		$phone = get_user_meta( $user_id, 'phone', true );
		if ( empty( $phone ) ) {
			$phone = get_user_meta( $user_id, 'billing_phone', true );
		}

		$roles = (array) $user->roles;
		$role  = ! empty( $roles ) ? reset( $roles ) : 'subscriber';

		wp_send_json_success( array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
			'phone'        => $phone,
			'role'         => $role,
		) );
	}

	public static function ajax_update_user() {
		check_ajax_referer( 'mbs_admin_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'movie-booking-system' ) ) );
		}

		$user_id      = intval( $_POST['user_id'] ?? 0 );
		$email        = sanitize_email( $_POST['email'] ?? '' );
		$display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
		$phone        = sanitize_text_field( $_POST['phone'] ?? '' );
		$role         = sanitize_text_field( $_POST['role'] ?? '' );

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'Người dùng không tồn tại.', 'movie-booking-system' ) ) );
		}

		if ( ! empty( $email ) ) {
			if ( ! is_email( $email ) ) {
				wp_send_json_error( array( 'message' => __( 'Email không hợp lệ.', 'movie-booking-system' ) ) );
			}
			$existing_user = email_exists( $email );
			if ( $existing_user && $existing_user != $user_id ) {
				wp_send_json_error( array( 'message' => __( 'Email này đã được sử dụng bởi tài khoản khác.', 'movie-booking-system' ) ) );
			}
		}

		$update_args = array( 'ID' => $user_id );
		if ( ! empty( $email ) ) {
			$update_args['user_email'] = $email;
		}
		if ( ! empty( $display_name ) ) {
			$update_args['display_name'] = $display_name;
		}

		$result = wp_update_user( $update_args );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! empty( $role ) ) {
			$allowed_roles = array( 'subscriber', 'cinema_staff', 'cinema_accountant', 'administrator' );
			if ( in_array( $role, $allowed_roles, true ) ) {
				if ( get_current_user_id() === $user_id && $role !== 'administrator' ) {
					// Don't let current admin demote their own role
				} else {
					$user_obj = new WP_User( $user_id );
					$user_obj->set_role( $role );
				}
			}
		}

		update_user_meta( $user_id, 'phone', $phone );
		update_user_meta( $user_id, 'billing_phone', $phone );

		wp_send_json_success( array( 'message' => __( 'Cập nhật thông tin người dùng thành công!', 'movie-booking-system' ) ) );
	}

	public static function ajax_update_user_password() {
		check_ajax_referer( 'mbs_admin_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'movie-booking-system' ) ) );
		}

		$user_id      = intval( $_POST['user_id'] ?? 0 );
		$new_password = sanitize_text_field( $_POST['new_password'] ?? '' );

		if ( empty( $new_password ) || strlen( $new_password ) < 6 ) {
			wp_send_json_error( array( 'message' => __( 'Mật khẩu phải có độ dài tối thiểu 6 ký tự.', 'movie-booking-system' ) ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy người dùng.', 'movie-booking-system' ) ) );
		}

		wp_set_password( $new_password, $user_id );

		wp_send_json_success( array(
			'message'      => sprintf( __( 'Đã cập nhật mật khẩu mới cho tài khoản "%s" thành công!', 'movie-booking-system' ), $user->user_login ),
			'user_login'   => $user->user_login,
			'new_password' => $new_password,
		) );
	}

	public static function ajax_delete_user() {
		check_ajax_referer( 'mbs_admin_nonce', '_ajax_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'movie-booking-system' ) ) );
		}

		$user_id = intval( $_POST['user_id'] ?? 0 );
		if ( $user_id === get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không thể xóa tài khoản của chính mình khi đang đăng nhập!', 'movie-booking-system' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';
		$deleted = wp_delete_user( $user_id );

		if ( $deleted ) {
			wp_send_json_success( array( 'message' => __( 'Đã xóa người dùng thành công!', 'movie-booking-system' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Có lỗi xảy ra khi xóa người dùng.', 'movie-booking-system' ) ) );
		}
	}
}
