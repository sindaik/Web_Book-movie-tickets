<?php
/**
 * Register Custom Post Types and Taxonomies
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_CPT {

	/**
	 * Hook registrations
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_custom_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Register Custom Post Types
	 */
	public static function register_post_types() {
		// 1. Movie CPT
		$labels_movie = array(
			'name'               => __( 'Phim Chiếu', 'movie-booking-system' ),
			'singular_name'      => __( 'Phim', 'movie-booking-system' ),
			'add_new'            => __( 'Thêm Phim Mới', 'movie-booking-system' ),
			'add_new_item'       => __( 'Thêm Phim Mới', 'movie-booking-system' ),
			'edit_item'          => __( 'Chỉnh Sửa Phim', 'movie-booking-system' ),
			'new_item'           => __( 'Phim Mới', 'movie-booking-system' ),
			'view_item'          => __( 'Xem Phim', 'movie-booking-system' ),
			'search_items'       => __( 'Tìm Kiếm Phim', 'movie-booking-system' ),
			'not_found'          => __( 'Không tìm thấy phim nào', 'movie-booking-system' ),
			'menu_name'          => __( 'Phim', 'movie-booking-system' ),
		);

		register_post_type( 'movie', array(
			'labels'              => $labels_movie,
			'public'              => true,
			'has_archive'         => true,
			'show_in_rest'        => true,
			'show_in_menu'        => false, // Managed under custom parent menu
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'rewrite'             => array( 'slug' => 'phim' ),
			'menu_icon'           => 'dashicons-format-video',
		) );

		// 2. Cinema CPT
		$labels_cinema = array(
			'name'               => __( 'Rạp Chiếu', 'movie-booking-system' ),
			'singular_name'      => __( 'Rạp', 'movie-booking-system' ),
			'add_new'            => __( 'Thêm Rạp Mới', 'movie-booking-system' ),
			'add_new_item'       => __( 'Thêm Rạp Mới', 'movie-booking-system' ),
			'edit_item'          => __( 'Chỉnh Sửa Rạp', 'movie-booking-system' ),
			'view_item'          => __( 'Xem Rạp', 'movie-booking-system' ),
			'menu_name'          => __( 'Rạp Phim', 'movie-booking-system' ),
		);

		register_post_type( 'cinema', array(
			'labels'              => $labels_cinema,
			'public'              => true,
			'has_archive'         => true,
			'show_in_rest'        => true,
			'show_in_menu'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'rewrite'             => array( 'slug' => 'rap' ),
			'menu_icon'           => 'dashicons-building',
		) );

		// 3. Showtime CPT
		$labels_showtime = array(
			'name'               => __( 'Suất Chiếu', 'movie-booking-system' ),
			'singular_name'      => __( 'Suất Chiếu', 'movie-booking-system' ),
			'add_new'            => __( 'Thêm Suất Chiếu', 'movie-booking-system' ),
			'add_new_item'       => __( 'Thêm Suất Chiếu Mới', 'movie-booking-system' ),
			'edit_item'          => __( 'Chỉnh Sửa Suất Chiếu', 'movie-booking-system' ),
			'menu_name'          => __( 'Suất Chiếu', 'movie-booking-system' ),
		);

		register_post_type( 'showtime', array(
			'labels'              => $labels_showtime,
			'public'              => true,
			'has_archive'         => false,
			'show_in_rest'        => true,
			'show_in_menu'        => false,
			'supports'            => array( 'title' ),
			'rewrite'             => array( 'slug' => 'suat-chieu' ),
			'menu_icon'           => 'dashicons-clock',
		) );
	}

	/**
	 * Register Taxonomies
	 */
	public static function register_taxonomies() {
		// Thể loại phim
		register_taxonomy( 'movie_genre', 'movie', array(
			'labels'            => array(
				'name'          => __( 'Thể Loại', 'movie-booking-system' ),
				'singular_name' => __( 'Thể Loại', 'movie-booking-system' ),
				'menu_name'     => __( 'Thể Loại', 'movie-booking-system' ),
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'the-loai' ),
		) );

		// Định dạng chiếu (2D, 3D, IMAX)
		register_taxonomy( 'movie_format', 'movie', array(
			'labels'            => array(
				'name'          => __( 'Định Dạng', 'movie-booking-system' ),
				'singular_name' => __( 'Định Dạng', 'movie-booking-system' ),
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'dinh-dang' ),
		) );
	}

	/**
	 * Add Meta Boxes
	 */
	public static function add_custom_meta_boxes() {
		add_meta_box(
			'mbs_movie_details',
			__( 'Chi Tiết Phim & Thông Tin Chiếu', 'movie-booking-system' ),
			array( __CLASS__, 'render_movie_meta_box' ),
			'movie',
			'normal',
			'high'
		);

		add_meta_box(
			'mbs_cinema_details',
			__( 'Thông Tin Rạp Chiếu', 'movie-booking-system' ),
			array( __CLASS__, 'render_cinema_meta_box' ),
			'cinema',
			'normal',
			'high'
		);

		add_meta_box(
			'mbs_showtime_details',
			__( 'Thông Tin Suất Chiếu', 'movie-booking-system' ),
			array( __CLASS__, 'render_showtime_meta_box' ),
			'showtime',
			'normal',
			'high'
		);
	}

	/**
	 * Render Movie Meta Box
	 */
	public static function render_movie_meta_box( $post ) {
		wp_nonce_field( 'mbs_save_movie_meta', 'mbs_movie_meta_nonce' );

		$trailer_url  = get_post_meta( $post->ID, '_mbs_trailer_url', true );
		$duration     = get_post_meta( $post->ID, '_mbs_duration', true );
		$director     = get_post_meta( $post->ID, '_mbs_director', true );
		$cast         = get_post_meta( $post->ID, '_mbs_cast', true );
		$release_date = get_post_meta( $post->ID, '_mbs_release_date', true );
		$rating_score = get_post_meta( $post->ID, '_mbs_rating_score', true ) ?: '8.5';
		$age_rating   = get_post_meta( $post->ID, '_mbs_age_rating', true ) ?: 'P';
		$movie_status = get_post_meta( $post->ID, '_mbs_movie_status', true ) ?: 'now_showing';
		$is_hot       = get_post_meta( $post->ID, '_mbs_is_hot', true );
		$language     = get_post_meta( $post->ID, '_mbs_language', true ) ?: 'Tiếng Anh - Phụ đề Tiếng Việt';
		?>
		<table class="form-table">
			<tr>
				<th><label for="mbs_trailer_url"><?php _e( 'Link Trailer YouTube', 'movie-booking-system' ); ?></label></th>
				<td>
					<input type="url" name="mbs_trailer_url" id="mbs_trailer_url" value="<?php echo esc_attr( $trailer_url ); ?>" class="large-text" placeholder="https://www.youtube.com/watch?v=..." />
					<p class="description"><?php _e( 'Nhập link YouTube đầy đủ để hệ thống tự động nhúng vào pop-up trailer.', 'movie-booking-system' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_duration"><?php _e( 'Thời Lượng (phút)', 'movie-booking-system' ); ?></label></th>
				<td><input type="number" name="mbs_duration" id="mbs_duration" value="<?php echo esc_attr( $duration ); ?>" class="regular-text" placeholder="120" /></td>
			</tr>
			<tr>
				<th><label for="mbs_director"><?php _e( 'Đạo Diễn', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_director" id="mbs_director" value="<?php echo esc_attr( $director ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="mbs_cast"><?php _e( 'Diễn Viên Chính', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_cast" id="mbs_cast" value="<?php echo esc_attr( $cast ); ?>" class="large-text" placeholder="Timothée Chalamet, Zendaya, ..." /></td>
			</tr>
			<tr>
				<th><label for="mbs_release_date"><?php _e( 'Ngày Khởi Chiếu', 'movie-booking-system' ); ?></label></th>
				<td><input type="date" name="mbs_release_date" id="mbs_release_date" value="<?php echo esc_attr( $release_date ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="mbs_rating_score"><?php _e( 'Điểm Đánh Giá (IMDb/Rating)', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_rating_score" id="mbs_rating_score" value="<?php echo esc_attr( $rating_score ); ?>" class="small-text" placeholder="8.9" /> / 10</td>
			</tr>
			<tr>
				<th><label for="mbs_age_rating"><?php _e( 'Phân Loại Độ Tuổi', 'movie-booking-system' ); ?></label></th>
				<td>
					<select name="mbs_age_rating" id="mbs_age_rating">
						<option value="P" <?php selected( $age_rating, 'P' ); ?>>P - Mọi lứa tuổi</option>
						<option value="K" <?php selected( $age_rating, 'K' ); ?>>K - Dưới 13 tuổi có người kèm</option>
						<option value="T13" <?php selected( $age_rating, 'T13' ); ?>>T13 - Khán giả từ 13 tuổi trở lên</option>
						<option value="T16" <?php selected( $age_rating, 'T16' ); ?>>T16 - Khán giả từ 16 tuổi trở lên</option>
						<option value="T18" <?php selected( $age_rating, 'T18' ); ?>>T18 - Khán giả từ 18 tuổi trở lên</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_language"><?php _e( 'Ngôn Ngữ / Bản Chiếu', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_language" id="mbs_language" value="<?php echo esc_attr( $language ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="mbs_movie_status"><?php _e( 'Trạng Thái Chiếu', 'movie-booking-system' ); ?></label></th>
				<td>
					<select name="mbs_movie_status" id="mbs_movie_status">
						<option value="now_showing" <?php selected( $movie_status, 'now_showing' ); ?>><?php _e( 'Đang Chiếu', 'movie-booking-system' ); ?></option>
						<option value="coming_soon" <?php selected( $movie_status, 'coming_soon' ); ?>><?php _e( 'Sắp Chiếu', 'movie-booking-system' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_is_hot"><?php _e( 'Phim Hot / Nổi Bật', 'movie-booking-system' ); ?></label></th>
				<td>
					<label>
						<input type="checkbox" name="mbs_is_hot" id="mbs_is_hot" value="1" <?php checked( $is_hot, '1' ); ?> />
						<?php _e( 'Hiển thị trên Slider / Hero Banner Trang Chủ', 'movie-booking-system' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Cinema Meta Box
	 */
	public static function render_cinema_meta_box( $post ) {
		wp_nonce_field( 'mbs_save_cinema_meta', 'mbs_cinema_meta_nonce' );

		$address  = get_post_meta( $post->ID, '_mbs_address', true );
		$city     = get_post_meta( $post->ID, '_mbs_city', true ) ?: 'Hồ Chí Minh';
		$hotline  = get_post_meta( $post->ID, '_mbs_hotline', true ) ?: '1900 6017';
		?>
		<table class="form-table">
			<tr>
				<th><label for="mbs_address"><?php _e( 'Địa Chỉ', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_address" id="mbs_address" value="<?php echo esc_attr( $address ); ?>" class="large-text" placeholder="Tầng 3, TTTM Vincom Center, 72 Lê Thánh Tôn, Q.1" /></td>
			</tr>
			<tr>
				<th><label for="mbs_city"><?php _e( 'Thành Phố / Tỉnh', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_city" id="mbs_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="mbs_hotline"><?php _e( 'Hotline', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_hotline" id="mbs_hotline" value="<?php echo esc_attr( $hotline ); ?>" class="regular-text" /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Showtime Meta Box
	 */
	public static function render_showtime_meta_box( $post ) {
		global $wpdb;
		wp_nonce_field( 'mbs_save_showtime_meta', 'mbs_showtime_meta_nonce' );

		$movie_id   = get_post_meta( $post->ID, '_mbs_movie_id', true );
		$cinema_id  = get_post_meta( $post->ID, '_mbs_cinema_id', true );
		$room_id    = get_post_meta( $post->ID, '_mbs_room_id', true );
		$show_date  = get_post_meta( $post->ID, '_mbs_show_date', true ) ?: date( 'Y-m-d' );
		$start_time = get_post_meta( $post->ID, '_mbs_start_time', true ) ?: '19:30';
		$end_time   = get_post_meta( $post->ID, '_mbs_end_time', true ) ?: '21:45';
		$base_price = get_post_meta( $post->ID, '_mbs_base_price', true ) ?: '95000';
		$format     = get_post_meta( $post->ID, '_mbs_format', true ) ?: '2D Phụ Đề';

		$movies = get_posts( array( 'post_type' => 'movie', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
		$cinemas = get_posts( array( 'post_type' => 'cinema', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
		$table_rooms = $wpdb->prefix . 'booking_rooms';
		$rooms = $wpdb->get_results( "SELECT * FROM $table_rooms WHERE status = 'active'" );
		?>
		<table class="form-table">
			<tr>
				<th><label for="mbs_movie_id"><?php _e( 'Chọn Phim', 'movie-booking-system' ); ?></label></th>
				<td>
					<select name="mbs_movie_id" id="mbs_movie_id" required>
						<option value=""><?php _e( '-- Chọn Phim --', 'movie-booking-system' ); ?></option>
						<?php foreach ( $movies as $m ) : ?>
							<option value="<?php echo esc_attr( $m->ID ); ?>" <?php selected( $movie_id, $m->ID ); ?>><?php echo esc_html( $m->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_cinema_id"><?php _e( 'Chọn Rạp', 'movie-booking-system' ); ?></label></th>
				<td>
					<select name="mbs_cinema_id" id="mbs_cinema_id" required>
						<option value=""><?php _e( '-- Chọn Rạp --', 'movie-booking-system' ); ?></option>
						<?php foreach ( $cinemas as $c ) : ?>
							<option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $cinema_id, $c->ID ); ?>><?php echo esc_html( $c->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_room_id"><?php _e( 'Phòng Chiếu', 'movie-booking-system' ); ?></label></th>
				<td>
					<select name="mbs_room_id" id="mbs_room_id" required>
						<option value=""><?php _e( '-- Chọn Phòng --', 'movie-booking-system' ); ?></option>
						<?php foreach ( $rooms as $r ) : ?>
							<option value="<?php echo esc_attr( $r->id ); ?>" data-cinema="<?php echo esc_attr( $r->cinema_id ); ?>" <?php selected( $room_id, $r->id ); ?>>
								<?php echo esc_html( $r->room_name . ' (' . $r->room_type . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="mbs_show_date"><?php _e( 'Ngày Chiếu', 'movie-booking-system' ); ?></label></th>
				<td><input type="date" name="mbs_show_date" id="mbs_show_date" value="<?php echo esc_attr( $show_date ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="mbs_start_time"><?php _e( 'Giờ Bắt Đầu', 'movie-booking-system' ); ?></label></th>
				<td><input type="time" name="mbs_start_time" id="mbs_start_time" value="<?php echo esc_attr( $start_time ); ?>" required /></td>
			</tr>
			<tr>
				<th><label for="mbs_end_time"><?php _e( 'Giờ Kết Thúc', 'movie-booking-system' ); ?></label></th>
				<td><input type="time" name="mbs_end_time" id="mbs_end_time" value="<?php echo esc_attr( $end_time ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="mbs_format"><?php _e( 'Định Dạng Suất Chiếu', 'movie-booking-system' ); ?></label></th>
				<td><input type="text" name="mbs_format" id="mbs_format" value="<?php echo esc_attr( $format ); ?>" class="regular-text" placeholder="2D Phụ Đề, 3D, IMAX..." /></td>
			</tr>
			<tr>
				<th><label for="mbs_base_price"><?php _e( 'Giá Vé Cơ Bản (VNĐ)', 'movie-booking-system' ); ?></label></th>
				<td><input type="number" step="5000" name="mbs_base_price" id="mbs_base_price" value="<?php echo esc_attr( $base_price ); ?>" class="regular-text" required /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Meta Box Data
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		// Prevent autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Movie Meta
		if ( $post->post_type === 'movie' && isset( $_POST['mbs_movie_meta_nonce'] ) && wp_verify_nonce( $_POST['mbs_movie_meta_nonce'], 'mbs_save_movie_meta' ) ) {
			$fields = array(
				'_mbs_trailer_url'  => 'esc_url_raw',
				'_mbs_duration'     => 'absint',
				'_mbs_director'     => 'sanitize_text_field',
				'_mbs_cast'         => 'sanitize_text_field',
				'_mbs_release_date' => 'sanitize_text_field',
				'_mbs_rating_score' => 'sanitize_text_field',
				'_mbs_age_rating'   => 'sanitize_text_field',
				'_mbs_movie_status' => 'sanitize_text_field',
				'_mbs_language'     => 'sanitize_text_field',
			);

			foreach ( $fields as $meta_key => $sanitizer ) {
				if ( isset( $_POST[ substr( $meta_key, 1 ) ] ) ) {
					$val = call_user_func( $sanitizer, $_POST[ substr( $meta_key, 1 ) ] );
					update_post_meta( $post_id, $meta_key, $val );
				}
			}

			$is_hot = isset( $_POST['mbs_is_hot'] ) ? 1 : 0;
			update_post_meta( $post_id, '_mbs_is_hot', $is_hot );
		}

		// Cinema Meta
		if ( $post->post_type === 'cinema' && isset( $_POST['mbs_cinema_meta_nonce'] ) && wp_verify_nonce( $_POST['mbs_cinema_meta_nonce'], 'mbs_save_cinema_meta' ) ) {
			if ( isset( $_POST['mbs_address'] ) ) {
				update_post_meta( $post_id, '_mbs_address', sanitize_text_field( $_POST['mbs_address'] ) );
			}
			if ( isset( $_POST['mbs_city'] ) ) {
				update_post_meta( $post_id, '_mbs_city', sanitize_text_field( $_POST['mbs_city'] ) );
			}
			if ( isset( $_POST['mbs_hotline'] ) ) {
				update_post_meta( $post_id, '_mbs_hotline', sanitize_text_field( $_POST['mbs_hotline'] ) );
			}
		}

		// Showtime Meta
		if ( $post->post_type === 'showtime' && isset( $_POST['mbs_showtime_meta_nonce'] ) && wp_verify_nonce( $_POST['mbs_showtime_meta_nonce'], 'mbs_save_showtime_meta' ) ) {
			$fields = array(
				'_mbs_movie_id'   => 'absint',
				'_mbs_cinema_id'  => 'absint',
				'_mbs_room_id'    => 'absint',
				'_mbs_show_date'  => 'sanitize_text_field',
				'_mbs_start_time' => 'sanitize_text_field',
				'_mbs_end_time'   => 'sanitize_text_field',
				'_mbs_format'     => 'sanitize_text_field',
				'_mbs_base_price' => 'floatval',
			);

			foreach ( $fields as $meta_key => $sanitizer ) {
				if ( isset( $_POST[ substr( $meta_key, 1 ) ] ) ) {
					$val = call_user_func( $sanitizer, $_POST[ substr( $meta_key, 1 ) ] );
					update_post_meta( $post_id, $meta_key, $val );
				}
			}

			// Auto update title if empty
			$movie = get_post( absint( $_POST['mbs_movie_id'] ?? 0 ) );
			if ( $movie ) {
				$title = sprintf( '%s - %s %s', $movie->post_title, sanitize_text_field( $_POST['mbs_show_date'] ?? '' ), sanitize_text_field( $_POST['mbs_start_time'] ?? '' ) );
				remove_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ), 10 );
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $title ) );
				add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
			}
		}
	}
}
