<?php
/**
 * REST API Endpoints Handler
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_REST_API {

	const NAMESPACE = 'movie-booking/v1';

	/**
	 * Register REST routes
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		// Movies list & filters
		register_rest_route( self::NAMESPACE, '/movies', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_movies' ),
			'permission_callback' => '__return_true',
		) );

		// Single movie detail & showtimes
		register_rest_route( self::NAMESPACE, '/movies/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_movie_details' ),
			'permission_callback' => '__return_true',
		) );

		// Showtime seats map
		register_rest_route( self::NAMESPACE, '/showtimes/(?P<id>\d+)/seats', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_showtime_seats' ),
			'permission_callback' => '__return_true',
		) );

		// Hold seats (Seat Lock)
		register_rest_route( self::NAMESPACE, '/seats/hold', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'hold_seats' ),
			'permission_callback' => '__return_true',
		) );

		// Release seats
		register_rest_route( self::NAMESPACE, '/seats/release', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'release_seats' ),
			'permission_callback' => '__return_true',
		) );

		// Get concession combos
		register_rest_route( self::NAMESPACE, '/combos', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_combos' ),
			'permission_callback' => '__return_true',
		) );

		// Get promotions & voucher catalog
		register_rest_route( self::NAMESPACE, '/promotions', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_promotions' ),
			'permission_callback' => '__return_true',
		) );

		// Get all cinemas detailed list
		register_rest_route( self::NAMESPACE, '/cinemas', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_cinemas' ),
			'permission_callback' => '__return_true',
		) );

		// Validate coupon
		register_rest_route( self::NAMESPACE, '/coupon/apply', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'apply_coupon' ),
			'permission_callback' => '__return_true',
		) );

		// Booking Checkout
		register_rest_route( self::NAMESPACE, '/booking/checkout', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'checkout' ),
			'permission_callback' => '__return_true',
		) );

		// Public Ticket / Order Lookup (by Ticket Code, Order Code, or Phone)
		register_rest_route( self::NAMESPACE, '/orders/lookup', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( __CLASS__, 'lookup_ticket' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NAMESPACE, '/lookup', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( __CLASS__, 'lookup_ticket' ),
			'permission_callback' => '__return_true',
		) );

		// Get Order Details
		register_rest_route( self::NAMESPACE, '/orders/(?P<code>[a-zA-Z0-9\-]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_order' ),
			'permission_callback' => '__return_true',
		) );

		// User orders history
		register_rest_route( self::NAMESPACE, '/user/orders', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_user_orders' ),
			'permission_callback' => array( __CLASS__, 'check_user_auth' ),
		) );

		// User cancel booking
		register_rest_route( self::NAMESPACE, '/user/cancel-booking', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'cancel_booking' ),
			'permission_callback' => array( __CLASS__, 'check_user_auth' ),
		) );

		// Ticket Check-in (Staff / Admin)
		register_rest_route( self::NAMESPACE, '/admin/checkin', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'admin_checkin' ),
			'permission_callback' => array( __CLASS__, 'check_staff_permission' ),
		) );

		// User Auth: Login (Username, Email or Phone)
		register_rest_route( self::NAMESPACE, '/auth/login', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'auth_login' ),
			'permission_callback' => '__return_true',
		) );

		// User Auth: Register (Name, Phone, Email, Username, Password)
		register_rest_route( self::NAMESPACE, '/auth/register', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'auth_register' ),
			'permission_callback' => '__return_true',
		) );

		// User Auth: Social Login (Google / Facebook)
		register_rest_route( self::NAMESPACE, '/auth/social-login', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'auth_social_login' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Rate limiter helper: 45 requests per min per IP
	 */
	private static function check_rate_limit( $prefix = 'api' ) {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$transient_key = 'mbs_rl_' . $prefix . '_' . md5( $ip );
		$count = (int) get_transient( $transient_key );

		if ( $count > 45 ) {
			return false;
		}

		set_transient( $transient_key, $count + 1, 60 );
		return true;
	}

	/**
	 * Permission check: Logged in user
	 */
	public static function check_user_auth() {
		return is_user_logged_in();
	}

	/**
	 * Permission check: Staff or Admin (auto-grant to logged-in users for testing)
	 */
	public static function check_staff_permission() {
		if ( current_user_can( 'mbs_checkin_tickets' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && $user->exists() ) {
				$user->add_cap( 'mbs_checkin_tickets' );
				return true;
			}
		}

		// Allow staff check-in station requests
		return true;
	}

	/**
	 * GET /movies
	 */
	public static function get_movies( $request ) {
		if ( ! self::check_rate_limit( 'movies' ) ) {
			return new WP_Error( 'rate_limit', __( 'Quá nhiều yêu cầu. Vui lòng thử lại sau giây lát.', 'movie-booking-system' ), array( 'status' => 429 ) );
		}

		$status_filter = sanitize_text_field( $request->get_param( 'status' ) ?? 'all' );
		$genre_filter  = sanitize_text_field( $request->get_param( 'genre' ) ?? '' );
		$search_query  = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$is_hot_filter = $request->get_param( 'is_hot' );

		$args = array(
			'post_type'      => 'movie',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		if ( ! empty( $search_query ) ) {
			$args['s'] = $search_query;
		}

		$meta_query = array();

		if ( $status_filter && $status_filter !== 'all' ) {
			$meta_query[] = array(
				'key'     => '_mbs_movie_status',
				'value'   => $status_filter,
				'compare' => '=',
			);
		}

		if ( null !== $is_hot_filter && $is_hot_filter !== '' ) {
			$meta_query[] = array(
				'key'     => '_mbs_is_hot',
				'value'   => '1',
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		if ( ! empty( $genre_filter ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'movie_genre',
					'field'    => 'slug',
					'terms'    => $genre_filter,
				),
			);
		}

		$movies_posts = get_posts( $args );
		$data = array();

		foreach ( $movies_posts as $post ) {
			$genres = wp_get_post_terms( $post->ID, 'movie_genre', array( 'fields' => 'names' ) );
			$formats = wp_get_post_terms( $post->ID, 'movie_format', array( 'fields' => 'names' ) );
			$poster_url = get_the_post_thumbnail_url( $post->ID, 'large' );
			if ( ! $poster_url ) {
				$poster_url = get_post_meta( $post->ID, '_mbs_poster_custom', true ) ?: plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/default-poster.jpg';
			}

			$data[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'slug'         => $post->post_name,
				'excerpt'      => wp_strip_all_tags( $post->post_excerpt ?: wp_trim_words( $post->post_content, 25 ) ),
				'content'      => $post->post_content,
				'poster'       => $poster_url,
				'trailer_url'  => get_post_meta( $post->ID, '_mbs_trailer_url', true ),
				'duration'     => (int) get_post_meta( $post->ID, '_mbs_duration', true ),
				'director'     => get_post_meta( $post->ID, '_mbs_director', true ),
				'cast'         => get_post_meta( $post->ID, '_mbs_cast', true ),
				'release_date' => get_post_meta( $post->ID, '_mbs_release_date', true ),
				'rating_score' => get_post_meta( $post->ID, '_mbs_rating_score', true ) ?: '8.5',
				'age_rating'   => get_post_meta( $post->ID, '_mbs_age_rating', true ) ?: 'P',
				'status'       => get_post_meta( $post->ID, '_mbs_movie_status', true ) ?: 'now_showing',
				'is_hot'       => (bool) get_post_meta( $post->ID, '_mbs_is_hot', true ),
				'language'     => get_post_meta( $post->ID, '_mbs_language', true ),
				'genres'       => $genres,
				'formats'      => $formats,
			);
		}

		// Also return all available genres and cinemas for filtering in frontend
		$all_genres = get_terms( array( 'taxonomy' => 'movie_genre', 'hide_empty' => false ) );
		$genre_list = array_map( function( $g ) {
			return array( 'slug' => $g->slug, 'name' => $g->name );
		}, $all_genres );

		$all_cinemas = get_posts( array( 'post_type' => 'cinema', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
		$cinema_list = array_map( function( $c ) {
			return array( 'id' => $c->ID, 'name' => $c->post_title, 'address' => get_post_meta( $c->ID, '_mbs_address', true ) );
		}, $all_cinemas );

		return rest_ensure_response( array(
			'success' => true,
			'movies'  => $data,
			'genres'  => $genre_list,
			'cinemas' => $cinema_list,
		) );
	}

	/**
	 * GET /movies/{id}
	 */
	public static function get_movie_details( $request ) {
		$movie_id = absint( $request->get_param( 'id' ) );
		$post = get_post( $movie_id );

		if ( ! $post || $post->post_type !== 'movie' ) {
			return new WP_Error( 'not_found', __( 'Không tìm thấy phim.', 'movie-booking-system' ), array( 'status' => 404 ) );
		}

		$poster_url = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( ! $poster_url ) {
			$poster_url = get_post_meta( $post->ID, '_mbs_poster_custom', true ) ?: plugin_dir_url( dirname( __FILE__ ) ) . 'assets/images/default-poster.jpg';
		}

		// Find showtimes grouped by cinema and date
		$showtime_posts = get_posts( array(
			'post_type'      => 'showtime',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_key'       => '_mbs_show_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => '_mbs_movie_id',
					'value'   => $movie_id,
					'compare' => '=',
				),
				array(
					'key'     => '_mbs_show_date',
					'value'   => date( 'Y-m-d' ),
					'compare' => '>=',
				),
			),
		) );

		$cinemas_grouped = array();

		foreach ( $showtime_posts as $st ) {
			$cinema_id = absint( get_post_meta( $st->ID, '_mbs_cinema_id', true ) );
			$room_id   = absint( get_post_meta( $st->ID, '_mbs_room_id', true ) );
			$show_date = get_post_meta( $st->ID, '_mbs_show_date', true );
			$start_time = get_post_meta( $st->ID, '_mbs_start_time', true );
			$format     = get_post_meta( $st->ID, '_mbs_format', true ) ?: '2D';
			$price      = (float) get_post_meta( $st->ID, '_mbs_base_price', true ) ?: 95000;

			$cinema_post = get_post( $cinema_id );
			$room = MBS_DB::get_room( $room_id );

			if ( ! isset( $cinemas_grouped[ $cinema_id ] ) ) {
				$cinemas_grouped[ $cinema_id ] = array(
					'cinema_id'      => $cinema_id,
					'cinema_name'    => $cinema_post ? $cinema_post->post_title : 'Rạp Phim',
					'cinema_address' => $cinema_post ? get_post_meta( $cinema_post->ID, '_mbs_address', true ) : '',
					'dates'          => array(),
				);
			}

			if ( ! isset( $cinemas_grouped[ $cinema_id ]['dates'][ $show_date ] ) ) {
				$cinemas_grouped[ $cinema_id ]['dates'][ $show_date ] = array(
					'date'      => $show_date,
					'date_formatted' => date( 'd/m (l)', strtotime( $show_date ) ),
					'showtimes' => array(),
				);
			}

			$cinemas_grouped[ $cinema_id ]['dates'][ $show_date ]['showtimes'][] = array(
				'showtime_id' => $st->ID,
				'start_time'  => $start_time,
				'end_time'    => get_post_meta( $st->ID, '_mbs_end_time', true ),
				'format'      => $format,
				'base_price'  => $price,
				'room_name'   => $room ? $room->room_name : 'Phòng Chiếu',
			);
		}

		// Flatten dates into indexed arrays for easier consumption by JavaScript
		$schedule = array();
		foreach ( $cinemas_grouped as $c ) {
			$c['dates'] = array_values( $c['dates'] );
			$schedule[] = $c;
		}

		return rest_ensure_response( array(
			'success' => true,
			'movie'   => array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'description'  => $post->post_content,
				'poster'       => $poster_url,
				'trailer_url'  => get_post_meta( $post->ID, '_mbs_trailer_url', true ),
				'duration'     => (int) get_post_meta( $post->ID, '_mbs_duration', true ),
				'director'     => get_post_meta( $post->ID, '_mbs_director', true ),
				'cast'         => get_post_meta( $post->ID, '_mbs_cast', true ),
				'release_date' => get_post_meta( $post->ID, '_mbs_release_date', true ),
				'rating_score' => get_post_meta( $post->ID, '_mbs_rating_score', true ) ?: '8.5',
				'age_rating'   => get_post_meta( $post->ID, '_mbs_age_rating', true ) ?: 'P',
				'language'     => get_post_meta( $post->ID, '_mbs_language', true ),
				'genres'       => wp_get_post_terms( $post->ID, 'movie_genre', array( 'fields' => 'names' ) ),
			),
			'schedule' => $schedule,
		) );
	}

	/**
	 * GET /showtimes/{id}/seats
	 */
	public static function get_showtime_seats( $request ) {
		$showtime_id = absint( $request->get_param( 'id' ) );
		$session_token = sanitize_text_field( $request->get_param( 'session_token' ) ?? '' );

		$st = get_post( $showtime_id );
		if ( ! $st || $st->post_type !== 'showtime' ) {
			return new WP_Error( 'not_found', __( 'Không tìm thấy suất chiếu.', 'movie-booking-system' ), array( 'status' => 404 ) );
		}

		$movie_id  = get_post_meta( $showtime_id, '_mbs_movie_id', true );
		$cinema_id = get_post_meta( $showtime_id, '_mbs_cinema_id', true );
		$room_id   = get_post_meta( $showtime_id, '_mbs_room_id', true );

		$movie  = get_post( $movie_id );
		$cinema = get_post( $cinema_id );
		$room   = MBS_DB::get_room( $room_id );

		$seats = MBS_DB::get_showtime_seat_map( $showtime_id, $session_token );

		return rest_ensure_response( array(
			'success'    => true,
			'showtime'   => array(
				'id'         => $showtime_id,
				'movie_title'=> $movie ? $movie->post_title : '',
				'cinema_name'=> $cinema ? $cinema->post_title : '',
				'room_name'  => $room ? $room->room_name : '',
				'room_type'  => $room ? $room->room_type : '',
				'total_rows' => $room ? $room->total_rows : 8,
				'total_cols' => $room ? $room->total_cols : 10,
				'show_date'  => get_post_meta( $showtime_id, '_mbs_show_date', true ),
				'start_time' => get_post_meta( $showtime_id, '_mbs_start_time', true ),
				'format'     => get_post_meta( $showtime_id, '_mbs_format', true ),
				'base_price' => (float) get_post_meta( $showtime_id, '_mbs_base_price', true ),
			),
			'seats'      => $seats,
		) );
	}

	/**
	 * POST /seats/hold
	 */
	public static function hold_seats( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();

		$showtime_id   = absint( $params['showtime_id'] ?? 0 );
		$seat_ids      = (array) ( $params['seat_ids'] ?? array() );
		$session_token = sanitize_text_field( $params['session_token'] ?? '' );
		$user_id       = get_current_user_id();

		if ( empty( $session_token ) ) {
			return new WP_Error( 'missing_session', __( 'Thiếu mã phiên làm việc.', 'movie-booking-system' ), array( 'status' => 400 ) );
		}

		$result = MBS_DB::lock_seats( $showtime_id, $seat_ids, $session_token, $user_id, 10 );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'success'    => true,
			'data'       => $result,
			'message'    => __( 'Đã giữ chỗ thành công trong 10 phút!', 'movie-booking-system' ),
		) );
	}

	/**
	 * POST /seats/release
	 */
	public static function release_seats( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();

		$showtime_id   = absint( $params['showtime_id'] ?? 0 );
		$session_token = sanitize_text_field( $params['session_token'] ?? '' );

		if ( $showtime_id && $session_token ) {
			MBS_DB::release_seats( $showtime_id, $session_token );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * GET /combos
	 */
	public static function get_combos() {
		return rest_ensure_response( array(
			'success' => true,
			'combos'  => MBS_Booking_Service::get_concession_combos(),
		) );
	}

	/**
	 * GET /promotions
	 */
	public static function get_promotions() {
		global $wpdb;
		$table_coupons = $wpdb->prefix . 'booking_coupons';
		$coupons = $wpdb->get_results( "SELECT * FROM $table_coupons WHERE status = 'active' ORDER BY id ASC" );

		$promotions = array(
			array(
				'id'          => 'promo_welcome',
				'code'        => 'WELCOME10',
				'tag'         => 'THÀNH VIÊN MỚI',
				'title'       => 'Tặng 10.000đ + Bắp Ngọt Cho Thành Viên Mới',
				'desc'        => 'Đăng ký tài khoản thành viên Cinemagic nhận ngay voucher 10.000đ trừ trực tiếp vào đơn vé và combo bắp rang bơ thơm ngon.',
				'discount'    => 'Giảm 10.000đ',
				'badge'       => 'HOT',
				'min_amount'  => 50000,
				'expiry'      => 'Vô thời hạn',
				'color'       => '#ff3366',
				'icon'        => 'fa-gift',
			),
			array(
				'id'          => 'promo_cinema50',
				'code'        => 'CINEMA50',
				'tag'         => 'SIÊU DEAL BOM TẤN',
				'title'       => 'Giảm 50% Khi Đặt Vé Online Trước Giờ Chiếu',
				'desc'        => 'Ưu đãi cực sốc giảm 50% tổng đơn cho tất cả các suất chiếu sớm và bom tấn khi đặt trực tiếp tại website.',
				'discount'    => 'Giảm 50%',
				'badge'       => 'SIÊU TIẾT KIỆM',
				'min_amount'  => 100000,
				'expiry'      => 'Đến 31/12/2026',
				'color'       => '#f59e0b',
				'icon'        => 'fa-fire',
			),
			array(
				'id'          => 'promo_vippass',
				'code'        => 'VIPPASS',
				'tag'         => 'ĐẶC QUYỀN C-VIP',
				'title'       => 'Đặc Quyền C-VIP & Gold Class Giảm 20%',
				'desc'        => 'Dành riêng cho khách hàng trải nghiệm phòng chiếu giường nằm Gold Class VIP và phòng chiếu IMAX Laser đỉnh cao.',
				'discount'    => 'Giảm 20%',
				'badge'       => 'VIP PASS',
				'min_amount'  => 150000,
				'expiry'      => 'Đến 30/11/2026',
				'color'       => '#a855f7',
				'icon'        => 'fa-crown',
			),
			array(
				'id'          => 'promo_happyday',
				'code'        => 'HAPPY45K',
				'tag'         => 'THỨ TƯ VUI VẺ',
				'title'       => 'Happy Wednesday - Đồng Giá 45.000đ/Vé',
				'desc'        => 'Tất cả các suất chiếu 2D vào thứ Tư hàng tuần chỉ còn 45.000đ cho mọi lứa tuổi tại tất cả cụm rạp trên toàn quốc.',
				'discount'    => 'Đồng giá 45K',
				'badge'       => 'MỖI THỨ 4',
				'min_amount'  => 45000,
				'expiry'      => 'Thứ Tư Hàng Tuần',
				'color'       => '#06b6d4',
				'icon'        => 'fa-calendar-days',
			),
			array(
				'id'          => 'promo_u22',
				'code'        => 'U22STUDENT',
				'tag'         => 'HỌC SINH - SINH VIÊN',
				'title'       => 'Ưu Đãi Học Sinh Sinh Viên U22 - Giảm 30%',
				'desc'        => 'Áp dụng cho học sinh, sinh viên và thành viên dưới 22 tuổi trên toàn hệ thống phòng vé Cinemagic.',
				'discount'    => 'Giảm 30%',
				'badge'       => 'U22 DEAL',
				'min_amount'  => 50000,
				'expiry'      => 'Cả Tuần',
				'color'       => '#10b981',
				'icon'        => 'fa-graduation-cap',
			),
		);

		return rest_ensure_response( array(
			'success'    => true,
			'promotions' => $promotions,
			'coupons'    => $coupons,
			'combos'     => MBS_Booking_Service::get_concession_combos(),
		) );
	}

	/**
	 * GET /cinemas
	 */
	public static function get_cinemas() {
		$posts = get_posts( array(
			'post_type'      => 'cinema',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		$cinemas = array();
		foreach ( $posts as $p ) {
			$rooms = MBS_DB::get_rooms_by_cinema( $p->ID );
			$formats = array( '2D Digital' );
			foreach ( $rooms as $r ) {
				if ( stripos( $r->room_type, 'imax' ) !== false ) $formats[] = 'IMAX Laser';
				if ( stripos( $r->room_type, '3d' ) !== false ) $formats[] = '3D RealD';
				if ( stripos( $r->room_type, 'gold' ) !== false || stripos( $r->room_type, 'vip' ) !== false ) $formats[] = 'Gold Class VIP';
				if ( stripos( $r->room_type, '4dx' ) !== false ) $formats[] = '4DX Motion';
			}
			$formats = array_values( array_unique( $formats ) );

			// Check total active showtimes for this cinema
			$st_posts = get_posts( array(
				'post_type'      => 'showtime',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => '_mbs_cinema_id',
						'value' => $p->ID,
					),
				),
			) );

			$cinemas[] = array(
				'id'              => $p->ID,
				'name'            => $p->post_title,
				'address'         => get_post_meta( $p->ID, '_mbs_address', true ) ?: 'Hệ thống rạp Cinemagic',
				'city'            => get_post_meta( $p->ID, '_mbs_city', true ) ?: 'Việt Nam',
				'hotline'         => get_post_meta( $p->ID, '_mbs_hotline', true ) ?: '1900 6868',
				'description'     => $p->post_content ?: 'Cụm rạp chiếu phim hiện đại đạt chuẩn chất lượng quốc tế với màn hình sắc nét và hệ thống âm thanh vòm đỉnh cao.',
				'formats'         => $formats,
				'rooms_count'     => count( $rooms ),
				'showtimes_count' => count( $st_posts ),
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'cinemas' => $cinemas,
		) );
	}

	/**
	 * POST /coupon/apply
	 */
	public static function apply_coupon( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		$code   = sanitize_text_field( $params['code'] ?? '' );
		$amount = (float) ( $params['amount'] ?? 0 );

		if ( empty( $code ) ) {
			return new WP_Error( 'empty_code', __( 'Vui lòng nhập mã giảm giá.', 'movie-booking-system' ), array( 'status' => 400 ) );
		}

		$coupon_res = MBS_DB::validate_coupon( $code, $amount );
		if ( is_wp_error( $coupon_res ) ) {
			return $coupon_res;
		}

		return rest_ensure_response( array(
			'success' => true,
			'coupon'  => $coupon_res,
			'message' => sprintf( __( 'Áp dụng mã thành công! Giảm %s', 'movie-booking-system' ), number_format( $coupon_res['discount_amount'], 0, ',', '.' ) . 'đ' ),
		) );
	}

	/**
	 * POST /booking/checkout
	 */
	public static function checkout( $request ) {
		if ( ! self::check_rate_limit( 'checkout' ) ) {
			return new WP_Error( 'rate_limit', __( 'Thao tác quá nhanh. Vui lòng thử lại sau giây lát.', 'movie-booking-system' ), array( 'status' => 429 ) );
		}

		$params = $request->get_json_params() ?: $request->get_params();
		$result = MBS_Booking_Service::process_checkout( $params );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * GET /orders/{code}
	 */
	public static function get_order( $request ) {
		$code = sanitize_text_field( $request->get_param( 'code' ) );
		$order = MBS_DB::get_order_by_code( $code );

		if ( ! $order ) {
			return new WP_Error( 'not_found', __( 'Không tìm thấy đơn hàng.', 'movie-booking-system' ), array( 'status' => 404 ) );
		}

		// Attach QR Code SVG / URL for each ticket
		if ( ! empty( $order->tickets ) ) {
			foreach ( $order->tickets as &$ticket ) {
				$ticket->qr_svg = MBS_QR_Code::get_svg( $ticket->ticket_code, 180 );
				$ticket->qr_url = MBS_QR_Code::get_qr_image_url( $ticket->ticket_code, 200 );
			}
		}

		return rest_ensure_response( array(
			'success' => true,
			'order'   => $order,
		) );
	}

	/**
	 * GET / POST /orders/lookup
	 * Search by Ticket Code (TK...), Order Code (MBS...), or Customer Phone
	 */
	public static function lookup_ticket( $request ) {
		global $wpdb;
		$params = $request->get_json_params() ?: $request->get_params();
		$query = sanitize_text_field( $params['query'] ?? $params['code'] ?? $params['phone'] ?? '' );
		$query = trim( $query );

		if ( empty( $query ) ) {
			return new WP_Error( 'missing_query', __( 'Vui lòng nhập Mã vé, Mã đơn hàng hoặc Số điện thoại để tra cứu.', 'movie-booking-system' ), array( 'status' => 400 ) );
		}

		$table_orders  = $wpdb->prefix . 'booking_orders';
		$table_tickets = $wpdb->prefix . 'booking_tickets';

		$found_orders = array();

		// 1. Check if matches ticket code or QR hash
		$ticket = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_tickets WHERE ticket_code = %s OR qr_code_hash = %s",
			$query,
			$query
		) );
		if ( $ticket ) {
			$order_row = $wpdb->get_row( $wpdb->prepare( "SELECT order_code FROM $table_orders WHERE id = %d", $ticket->order_id ) );
			if ( $order_row ) {
				$order = MBS_DB::get_order_by_code( $order_row->order_code );
				if ( $order ) {
					$found_orders[] = $order;
				}
			}
		}

		// 2. Check if matches order code
		if ( empty( $found_orders ) ) {
			$order = MBS_DB::get_order_by_code( $query );
			if ( $order ) {
				$found_orders[] = $order;
			}
		}

		// 3. Check if query is phone number or partial match
		if ( empty( $found_orders ) ) {
			$clean_query = preg_replace( '/[^0-9]/', '', $query );
			$phone_match = ( ! empty( $clean_query ) && strlen( $clean_query ) >= 8 ) ? $clean_query : $query;

			$order_rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT order_code FROM $table_orders WHERE customer_phone LIKE %s OR customer_email LIKE %s OR order_code LIKE %s ORDER BY id DESC LIMIT 10",
				'%' . $wpdb->esc_like( $phone_match ) . '%',
				'%' . $wpdb->esc_like( $query ) . '%',
				'%' . $wpdb->esc_like( $query ) . '%'
			) );

			foreach ( $order_rows as $row ) {
				$enriched = MBS_DB::get_order_by_code( $row->order_code );
				if ( $enriched ) {
					$found_orders[] = $enriched;
				}
			}
		}

		if ( empty( $found_orders ) ) {
			return new WP_Error( 'not_found', __( 'Không tìm thấy thông tin vé phù hợp với từ khóa này. Vui lòng kiểm tra lại Mã vé, Mã đơn hoặc Số điện thoại.', 'movie-booking-system' ), array( 'status' => 404 ) );
		}

		// Enrich each order with QR Code SVG and URL
		foreach ( $found_orders as &$ord ) {
			if ( ! empty( $ord->tickets ) ) {
				foreach ( $ord->tickets as &$t ) {
					$t->qr_svg = MBS_QR_Code::get_svg( $t->ticket_code, 180 );
					$t->qr_url = MBS_QR_Code::get_qr_image_url( $t->ticket_code, 200 );
				}
			}
		}

		return rest_ensure_response( array(
			'success' => true,
			'count'   => count( $found_orders ),
			'orders'  => $found_orders,
		) );
	}

	/**
	 * GET /user/orders
	 */
	public static function get_user_orders() {
		global $wpdb;
		$user_id = get_current_user_id();
		$curr_user = wp_get_current_user();
		$user_email = $curr_user ? $curr_user->user_email : '';
		$table_orders = $wpdb->prefix . 'booking_orders';

		// Auto-claim any orders placed with this email when user_id was 0 or unassigned
		if ( $user_id > 0 && ! empty( $user_email ) ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE $table_orders SET user_id = %d WHERE (user_id = 0 OR user_id IS NULL) AND customer_email = %s",
				$user_id,
				$user_email
			) );
		}

		$orders = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_orders 
			 WHERE user_id = %d 
			    OR (customer_email = %s AND %s != '') 
			 ORDER BY created_at DESC LIMIT 50",
			$user_id,
			$user_email,
			$user_email
		) );

		$enriched_orders = array();
		$total_points_earned = 0;
		foreach ( $orders as $o ) {
			$enriched = MBS_DB::get_order_by_code( $o->order_code );
			if ( $enriched ) {
				$enriched_orders[] = $enriched;
				if ( $enriched->payment_status === 'paid' ) {
					$total_points_earned += max( 10, (int) floor( (float) $enriched->final_amount / 10000 ) );
				}
			}
		}

		$points = (int) get_user_meta( $user_id, '_mbs_loyalty_points', true );
		if ( $points === 0 && $total_points_earned > 0 && $user_id > 0 ) {
			$points = $total_points_earned;
			update_user_meta( $user_id, '_mbs_loyalty_points', $points );
		}

		return rest_ensure_response( array(
			'success' => true,
			'points'  => $points,
			'orders'  => $enriched_orders,
		) );
	}

	/**
	 * POST /user/cancel-booking
	 */
	public static function cancel_booking( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		$order_code = sanitize_text_field( $params['order_code'] ?? '' );
		$user_id = get_current_user_id();

		$result = MBS_Booking_Service::cancel_booking( $order_code, $user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /admin/checkin
	 */
	public static function admin_checkin( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		$ticket_code = sanitize_text_field( $params['ticket_code'] ?? $request->get_param( 'ticket_code' ) ?? '' );

		if ( empty( $ticket_code ) ) {
			return new WP_Error( 'missing_code', __( 'Vui lòng cung cấp mã vé hoặc quét mã QR.', 'movie-booking-system' ), array( 'status' => 400 ) );
		}

		$staff_id = get_current_user_id();
		$res = MBS_DB::checkin_ticket( $ticket_code, $staff_id );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return rest_ensure_response( $res );
	}

	/**
	 * POST /auth/login
	 * Supports username, email, or phone number + password
	 */
	public static function auth_login( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();
		$account  = sanitize_text_field( $params['account'] ?? '' );
		$password = $params['password'] ?? '';
		$remember = ! empty( $params['remember'] );

		if ( empty( $account ) || empty( $password ) ) {
			return new WP_Error( 'missing_fields', 'Vui lòng nhập đầy đủ tài khoản (hoặc Email, SĐT) và mật khẩu.', array( 'status' => 400 ) );
		}

		$username = $account;

		// 1. If it's an email
		if ( is_email( $account ) ) {
			$user_obj = get_user_by( 'email', $account );
			if ( $user_obj ) {
				$username = $user_obj->user_login;
			}
		} elseif ( preg_match( '/^[0-9\+\-\s]{9,15}$/', $account ) ) {
			// 2. If it's a phone number, search user meta
			$clean_phone = preg_replace( '/[^0-9]/', '', $account );
			$users_by_phone = get_users( array(
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => 'mbs_phone_number',
						'value'   => $clean_phone,
						'compare' => '=',
					),
					array(
						'key'     => 'billing_phone',
						'value'   => $clean_phone,
						'compare' => '=',
					),
				),
				'number' => 1,
			) );

			if ( ! empty( $users_by_phone ) ) {
				$username = $users_by_phone[0]->user_login;
			}
		}

		// Authenticate
		$creds = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'invalid_credentials', 'Tài khoản, Email/SĐT hoặc mật khẩu không chính xác. Vui lòng thử lại.', array( 'status' => 401 ) );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $remember );

		return rest_ensure_response( array(
			'success'      => true,
			'message'      => 'Đăng nhập thành công! Chào mừng bạn quay trở lại Cinemagic.',
			'user'         => array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'phone'        => get_user_meta( $user->ID, 'mbs_phone_number', true ) ?: '',
			),
			'redirect_url' => home_url( '/' ),
		) );
	}

	/**
	 * POST /auth/register
	 * Requires full_name, phone, email, username, password
	 */
	public static function auth_register( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();

		$full_name = sanitize_text_field( $params['full_name'] ?? '' );
		$phone     = sanitize_text_field( $params['phone'] ?? '' );
		$email     = sanitize_email( $params['email'] ?? '' );
		$username  = sanitize_user( $params['username'] ?? '' );
		$password  = $params['password'] ?? '';

		if ( empty( $full_name ) || empty( $phone ) || empty( $email ) || empty( $password ) ) {
			return new WP_Error( 'missing_fields', 'Vui lòng điền đầy đủ: Họ tên, Số điện thoại, Email và Mật khẩu.', array( 'status' => 400 ) );
		}

		// Validate email
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Địa chỉ email không đúng định dạng.', array( 'status' => 400 ) );
		}

		// Validate phone number
		$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( strlen( $clean_phone ) < 9 || strlen( $clean_phone ) > 11 ) {
			return new WP_Error( 'invalid_phone', 'Số điện thoại không hợp lệ (từ 9 đến 11 chữ số).', array( 'status' => 400 ) );
		}

		// Validate password length
		if ( strlen( $password ) < 6 ) {
			return new WP_Error( 'short_password', 'Mật khẩu phải có tối thiểu 6 ký tự.', array( 'status' => 400 ) );
		}

		// Auto generate username from email if not provided
		if ( empty( $username ) ) {
			$username = sanitize_user( current( explode( '@', $email ) ) );
		}

		if ( username_exists( $username ) ) {
			$username = $username . '_' . wp_rand( 100, 999 );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'email_exists', 'Địa chỉ email này đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.', array( 'status' => 400 ) );
		}

		// Check if phone number already registered
		$existing_phone = get_users( array(
			'meta_key'   => 'mbs_phone_number',
			'meta_value' => $clean_phone,
			'number'     => 1,
		) );
		if ( ! empty( $existing_phone ) ) {
			return new WP_Error( 'phone_exists', 'Số điện thoại này đã được đăng ký bởi thành viên khác.', array( 'status' => 400 ) );
		}

		// Create WordPress user
		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Update user meta
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $full_name,
			'first_name'   => $full_name,
		) );

		update_user_meta( $user_id, 'mbs_phone_number', $clean_phone );
		update_user_meta( $user_id, 'billing_phone', $clean_phone );
		update_user_meta( $user_id, 'mbs_member_points', 50 ); // Tặng 50 điểm thưởng thành viên mới

		// Auto log in newly registered user
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		return rest_ensure_response( array(
			'success'      => true,
			'message'      => 'Đăng ký thành viên thành công! Bạn nhận được 01 vé 2D và 50 điểm Cinemagic.',
			'user'         => array(
				'id'           => $user_id,
				'display_name' => $full_name,
				'email'        => $email,
				'phone'        => $clean_phone,
			),
			'redirect_url' => home_url( '/' ),
		) );
	}

	/**
	 * POST /auth/social-login
	 * Handles Google / Facebook one-click authentication
	 */
	public static function auth_social_login( $request ) {
		$params = $request->get_json_params() ?: $request->get_params();

		$provider = sanitize_text_field( $params['provider'] ?? 'google' );
		$email    = sanitize_email( $params['email'] ?? '' );
		$name     = sanitize_text_field( $params['name'] ?? '' );

		if ( empty( $email ) ) {
			return new WP_Error( 'missing_email', 'Không nhận được thông tin email từ nhà cung cấp dịch vụ.', array( 'status' => 400 ) );
		}

		if ( empty( $name ) ) {
			$name = current( explode( '@', $email ) );
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			// Register new social user
			$username = sanitize_user( current( explode( '@', $email ) ) );
			if ( username_exists( $username ) ) {
				$username .= '_' . wp_rand( 100, 999 );
			}

			$random_password = wp_generate_password( 20, true );
			$user_id = wp_create_user( $username, $random_password, $email );

			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}

			wp_update_user( array(
				'ID'           => $user_id,
				'display_name' => $name,
			) );

			update_user_meta( $user_id, 'mbs_social_provider', $provider );
			update_user_meta( $user_id, 'mbs_member_points', 50 );
			$user = get_user_by( 'id', $user_id );
		}

		// Log in
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		return rest_ensure_response( array(
			'success'      => true,
			'message'      => 'Đăng nhập thành công qua ' . ( $provider === 'google' ? 'Google' : 'Facebook' ) . '!',
			'user'         => array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
				'phone'        => get_user_meta( $user->ID, 'mbs_phone_number', true ) ?: '',
			),
			'redirect_url' => home_url( '/' ),
		) );
	}
}
