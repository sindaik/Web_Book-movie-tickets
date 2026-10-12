<?php
/**
 * Database operations handler using $wpdb
 * Supports transactions, row locking, and custom table queries
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_DB {

	/**
	 * Get Room by ID
	 */
	public static function get_room( $room_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'booking_rooms';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $room_id ) );
	}

	/**
	 * Get Rooms by Cinema ID
	 */
	public static function get_rooms_by_cinema( $cinema_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'booking_rooms';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE cinema_id = %d AND status = 'active' ORDER BY id ASC", $cinema_id ) );
	}

	/**
	 * Save or Update Room & Generate Seats
	 */
	public static function save_room( $data ) {
		global $wpdb;
		$table_rooms = $wpdb->prefix . 'booking_rooms';
		$table_seats = $wpdb->prefix . 'booking_seats';

		$room_id = ! empty( $data['id'] ) ? absint( $data['id'] ) : 0;
		$cinema_id = absint( $data['cinema_id'] );
		$room_name = sanitize_text_field( $data['room_name'] );
		$room_type = sanitize_text_field( $data['room_type'] ?: 'Standard' );
		$total_rows = max( 1, min( 26, absint( $data['total_rows'] ?: 8 ) ) );
		$total_cols = max( 1, min( 30, absint( $data['total_cols'] ?: 10 ) ) );
		$seat_layout = isset( $data['seat_layout'] ) ? ( is_string( $data['seat_layout'] ) ? $data['seat_layout'] : wp_json_encode( $data['seat_layout'] ) ) : null;

		if ( $room_id > 0 ) {
			$wpdb->update(
				$table_rooms,
				array(
					'cinema_id'   => $cinema_id,
					'room_name'   => $room_name,
					'room_type'   => $room_type,
					'total_rows'  => $total_rows,
					'total_cols'  => $total_cols,
					'seat_layout' => $seat_layout,
				),
				array( 'id' => $room_id ),
				array( '%d', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table_rooms,
				array(
					'cinema_id'   => $cinema_id,
					'room_name'   => $room_name,
					'room_type'   => $room_type,
					'total_rows'  => $total_rows,
					'total_cols'  => $total_cols,
					'seat_layout' => $seat_layout,
					'status'      => 'active',
				),
				array( '%d', '%s', '%s', '%d', '%d', '%s', '%s' )
			);
			$room_id = $wpdb->insert_id;
		}

		// Rebuild seats for room if layout provided or new
		self::generate_room_seats( $room_id, $total_rows, $total_cols, $seat_layout );

		return $room_id;
	}

	/**
	 * Generate Seats for Room
	 */
	public static function generate_room_seats( $room_id, $total_rows, $total_cols, $layout_json = null ) {
		global $wpdb;
		$table_seats = $wpdb->prefix . 'booking_seats';

		$layout_matrix = array();
		if ( ! empty( $layout_json ) ) {
			$decoded = json_decode( $layout_json, true );
			if ( is_array( $decoded ) ) {
				$layout_matrix = $decoded;
			}
		}

		// Delete existing seats for room
		$wpdb->delete( $table_seats, array( 'room_id' => $room_id ), array( '%d' ) );

		$row_chars = range( 'A', 'Z' );
		for ( $r = 0; $r < $total_rows; $r++ ) {
			$row_letter = $row_chars[ $r ];
			for ( $c = 1; $c <= $total_cols; $c++ ) {
				$seat_key = $row_letter . $c;
				$seat_type = 'regular';
				$modifier = 0.00;

				// Check layout matrix or apply smart defaults:
				// Rows A-C: Regular
				// Rows D-F: VIP
				// Last Row: Couple
				if ( isset( $layout_matrix[ $seat_key ] ) && ! empty( $layout_matrix[ $seat_key ]['type'] ) ) {
					$seat_type = sanitize_text_field( $layout_matrix[ $seat_key ]['type'] );
				} else {
					if ( $r === $total_rows - 1 ) {
						$seat_type = 'couple';
						$modifier = 40000.00;
					} elseif ( $r >= 3 && $r <= 5 ) {
						$seat_type = 'vip';
						$modifier = 20000.00;
					} else {
						$seat_type = 'regular';
						$modifier = 0.00;
					}
				}

				if ( $seat_type === 'vip' ) {
					$modifier = 20000.00;
				} elseif ( $seat_type === 'couple' ) {
					$modifier = 40000.00;
				}

				$wpdb->insert(
					$table_seats,
					array(
						'room_id'              => $room_id,
						'seat_row'             => $row_letter,
						'seat_number'          => $c,
						'seat_type'            => $seat_type,
						'seat_price_modifier'  => $modifier,
						'status'               => 'active',
					),
					array( '%d', '%s', '%d', '%s', '%f', '%s' )
				);
			}
		}
	}

	/**
	 * Get Seats for Room
	 */
	public static function get_seats_by_room( $room_id ) {
		global $wpdb;
		$table_seats = $wpdb->prefix . 'booking_seats';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_seats WHERE room_id = %d AND status = 'active' ORDER BY seat_row ASC, seat_number ASC",
			$room_id
		) );
	}

	/**
	 * Clean expired seat locks
	 */
	public static function clean_expired_locks() {
		global $wpdb;
		$table_locks = $wpdb->prefix . 'booking_seat_locks';
		$wpdb->query( "DELETE FROM $table_locks WHERE expires_at < NOW()" );
	}

	/**
	 * Get status of all seats for a given showtime
	 * Returns seat map with status: 'available', 'locked' (held with countdown), 'booked'
	 */
	public static function get_showtime_seat_map( $showtime_id, $current_session = '' ) {
		global $wpdb;
		self::clean_expired_locks();

		$room_id = absint( get_post_meta( $showtime_id, '_mbs_room_id', true ) );
		if ( ! $room_id ) {
			return array();
		}

		$seats = self::get_seats_by_room( $room_id );
		$table_tickets = $wpdb->prefix . 'booking_tickets';
		$table_locks   = $wpdb->prefix . 'booking_seat_locks';

		// Get all booked seat IDs for this showtime
		$booked_seat_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT seat_id FROM $table_tickets WHERE showtime_id = %d",
			$showtime_id
		) );
		$booked_map = array_flip( $booked_seat_ids );

		// Get all locked seat IDs for this showtime
		$locks = $wpdb->get_results( $wpdb->prepare(
			"SELECT seat_id, session_token, UNIX_TIMESTAMP(expires_at) - UNIX_TIMESTAMP(NOW()) AS remaining_seconds 
			 FROM $table_locks WHERE showtime_id = %d AND expires_at > NOW()",
			$showtime_id
		) );
		$lock_map = array();
		foreach ( $locks as $lock ) {
			$lock_map[ $lock->seat_id ] = array(
				'is_mine'   => ( $current_session && $lock->session_token === $current_session ),
				'remaining' => max( 0, (int) $lock->remaining_seconds ),
			);
		}

		$base_price = (float) get_post_meta( $showtime_id, '_mbs_base_price', true ) ?: 95000;

		$result = array();
		foreach ( $seats as $s ) {
			$status = 'available';
			$is_mine = false;
			$remaining = 0;

			if ( isset( $booked_map[ $s->id ] ) ) {
				$status = 'booked';
			} elseif ( isset( $lock_map[ $s->id ] ) ) {
				$status = 'locked';
				$is_mine = $lock_map[ $s->id ]['is_mine'];
				$remaining = $lock_map[ $s->id ]['remaining'];
				if ( $is_mine ) {
					$status = 'selected'; // User's own held seat
				}
			}

			$seat_price = $base_price + (float) $s->seat_price_modifier;

			$result[] = array(
				'id'             => (int) $s->id,
				'row'            => $s->seat_row,
				'number'         => (int) $s->seat_number,
				'label'          => $s->seat_row . sprintf( '%02d', $s->seat_number ),
				'type'           => $s->seat_type,
				'price'          => $seat_price,
				'price_formatted'=> number_format( $seat_price, 0, ',', '.' ) . 'đ',
				'status'         => $status,
				'is_mine'        => $is_mine,
				'remaining'      => $remaining,
			);
		}

		return $result;
	}

	/**
	 * Lock seats with Database Transaction to prevent race condition
	 */
	public static function lock_seats( $showtime_id, $seat_ids, $session_token, $user_id = 0, $duration_minutes = 10 ) {
		global $wpdb;
		self::clean_expired_locks();

		$table_locks   = $wpdb->prefix . 'booking_seat_locks';
		$table_tickets = $wpdb->prefix . 'booking_tickets';

		if ( empty( $seat_ids ) || ! is_array( $seat_ids ) ) {
			return new WP_Error( 'invalid_seats', __( 'Danh sách ghế không hợp lệ.', 'movie-booking-system' ) );
		}

		// Clean and sanitize seat IDs
		$seat_ids = array_map( 'absint', $seat_ids );

		// START DB TRANSACTION
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Check if any seat is already booked
			$placeholders = implode( ',', array_fill( 0, count( $seat_ids ), '%d' ) );
			$query_booked = $wpdb->prepare(
				"SELECT seat_label FROM $table_tickets WHERE showtime_id = %d AND seat_id IN ($placeholders)",
				array_merge( array( $showtime_id ), $seat_ids )
			);
			$already_booked = $wpdb->get_col( $query_booked );

			if ( ! empty( $already_booked ) ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'seat_already_booked', sprintf( __( 'Ghế %s đã được mua bởi khách hàng khác.', 'movie-booking-system' ), implode( ', ', $already_booked ) ) );
			}

			// Check if any seat is locked by someone else
			$query_locked = $wpdb->prepare(
				"SELECT seat_id, session_token FROM $table_locks WHERE showtime_id = %d AND seat_id IN ($placeholders) AND expires_at > NOW() FOR UPDATE",
				array_merge( array( $showtime_id ), $seat_ids )
			);
			$active_locks = $wpdb->get_results( $query_locked );

			foreach ( $active_locks as $lock ) {
				if ( $lock->session_token !== $session_token ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'seat_locked_by_other', __( 'Một trong các ghế bạn chọn đang được khách hàng khác giữ chỗ. Vui lòng chọn ghế khác!', 'movie-booking-system' ) );
				}
			}

			// Clear existing locks for this session on this showtime
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM $table_locks WHERE showtime_id = %d AND session_token = %s",
				$showtime_id,
				$session_token
			) );

			// Insert new locks using MySQL functions to guarantee timezone consistency
			foreach ( $seat_ids as $sid ) {
				$inserted = $wpdb->query( $wpdb->prepare(
					"REPLACE INTO $table_locks (showtime_id, seat_id, session_token, user_id, locked_at, expires_at) 
					 VALUES (%d, %d, %s, %d, NOW(), DATE_ADD(NOW(), INTERVAL %d MINUTE))",
					$showtime_id,
					$sid,
					$session_token,
					$user_id,
					$duration_minutes
				) );

				if ( false === $inserted ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'lock_failed', __( 'Không thể giữ chỗ ghế. Vui lòng thử lại!', 'movie-booking-system' ) );
				}
			}

			$expires_at = $wpdb->get_var( $wpdb->prepare(
				"SELECT expires_at FROM $table_locks WHERE showtime_id = %d AND seat_id = %d",
				$showtime_id,
				$seat_ids[0]
			) );

			// COMMIT DB TRANSACTION
			$wpdb->query( 'COMMIT' );

			return array(
				'success'    => true,
				'seat_ids'   => $seat_ids,
				'expires_at' => $expires_at,
				'duration'   => $duration_minutes * 60,
			);

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'transaction_error', $e->getMessage() );
		}
	}

	/**
	 * Release held seats
	 */
	public static function release_seats( $showtime_id, $session_token ) {
		global $wpdb;
		$table_locks = $wpdb->prefix . 'booking_seat_locks';
		return $wpdb->query( $wpdb->prepare(
			"DELETE FROM $table_locks WHERE showtime_id = %d AND session_token = %s",
			$showtime_id,
			$session_token
		) );
	}

	/**
	 * Create Order & Tickets with Transaction
	 */
	public static function create_order( $order_data, $tickets_data ) {
		global $wpdb;
		$table_orders  = $wpdb->prefix . 'booking_orders';
		$table_tickets = $wpdb->prefix . 'booking_tickets';
		$table_locks   = $wpdb->prefix . 'booking_seat_locks';

		$wpdb->query( 'START TRANSACTION' );

		try {
			// Insert Order
			$inserted_order = $wpdb->insert(
				$table_orders,
				array(
					'order_code'      => $order_data['order_code'],
					'user_id'         => absint( $order_data['user_id'] ?? 0 ),
					'customer_name'   => sanitize_text_field( $order_data['customer_name'] ),
					'customer_email'  => sanitize_email( $order_data['customer_email'] ),
					'customer_phone'  => sanitize_text_field( $order_data['customer_phone'] ),
					'total_amount'    => (float) $order_data['total_amount'],
					'discount_amount' => (float) ( $order_data['discount_amount'] ?? 0 ),
					'final_amount'    => (float) $order_data['final_amount'],
					'combos_json'     => is_array( $order_data['combos'] ?? null ) ? wp_json_encode( $order_data['combos'] ) : '',
					'coupon_code'     => sanitize_text_field( $order_data['coupon_code'] ?? '' ),
					'payment_method'  => sanitize_text_field( $order_data['payment_method'] ?? 'sandbox' ),
					'payment_status'  => sanitize_text_field( $order_data['payment_status'] ?? 'paid' ),
					'transaction_id'  => sanitize_text_field( $order_data['transaction_id'] ?? '' ),
					'created_at'      => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( ! $inserted_order ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'order_creation_failed', __( 'Không thể tạo đơn hàng.', 'movie-booking-system' ) );
			}

			$order_id = $wpdb->insert_id;

			// Insert Tickets
			foreach ( $tickets_data as $ticket ) {
				$inserted_ticket = $wpdb->insert(
					$table_tickets,
					array(
						'order_id'       => $order_id,
						'showtime_id'    => absint( $ticket['showtime_id'] ),
						'seat_id'        => absint( $ticket['seat_id'] ),
						'seat_label'     => sanitize_text_field( $ticket['seat_label'] ),
						'seat_type'      => sanitize_text_field( $ticket['seat_type'] ),
						'price'          => (float) $ticket['price'],
						'ticket_code'    => sanitize_text_field( $ticket['ticket_code'] ),
						'qr_code_hash'   => sanitize_text_field( $ticket['qr_code_hash'] ),
						'checkin_status' => 'pending',
						'created_at'     => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
				);

				if ( ! $inserted_ticket ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'ticket_creation_failed', __( 'Không thể tạo vé.', 'movie-booking-system' ) );
				}
			}

			// Clear temporary seat locks
			if ( ! empty( $order_data['session_token'] ) && ! empty( $tickets_data[0]['showtime_id'] ) ) {
				$wpdb->query( $wpdb->prepare(
					"DELETE FROM $table_locks WHERE showtime_id = %d AND session_token = %s",
					$tickets_data[0]['showtime_id'],
					$order_data['session_token']
				) );
			}

			// Update coupon used count if used
			if ( ! empty( $order_data['coupon_code'] ) ) {
				$table_coupons = $wpdb->prefix . 'booking_coupons';
				$wpdb->query( $wpdb->prepare(
					"UPDATE $table_coupons SET used_count = used_count + 1 WHERE code = %s",
					$order_data['coupon_code']
				) );
			}

			// Award loyalty points to registered user
			if ( ! empty( $order_data['user_id'] ) ) {
				$points_earned = floor( $order_data['final_amount'] / 10000 ); // 1 point per 10,000 VND
				$current_points = (int) get_user_meta( $order_data['user_id'], '_mbs_loyalty_points', true );
				update_user_meta( $order_data['user_id'], '_mbs_loyalty_points', $current_points + $points_earned );
			}

			$wpdb->query( 'COMMIT' );

			return array(
				'order_id'   => $order_id,
				'order_code' => $order_data['order_code'],
			);

		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'order_transaction_error', $e->getMessage() );
		}
	}

	/**
	 * Get Order with Tickets by Order Code
	 */
	public static function get_order_by_code( $order_code ) {
		global $wpdb;
		$table_orders  = $wpdb->prefix . 'booking_orders';
		$table_tickets = $wpdb->prefix . 'booking_tickets';

		$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_orders WHERE order_code = %s", $order_code ) );
		if ( ! $order ) {
			return null;
		}

		$tickets = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE order_id = %d", $order->id ) );
		$order->tickets = $tickets;
		$order->combos  = ! empty( $order->combos_json ) ? json_decode( $order->combos_json, true ) : array();

		// Attach showtime and movie information
		if ( ! empty( $tickets ) ) {
			$showtime_id = $tickets[0]->showtime_id;
			$order->showtime_id = $showtime_id;
			$movie_id = get_post_meta( $showtime_id, '_mbs_movie_id', true );
			$cinema_id = get_post_meta( $showtime_id, '_mbs_cinema_id', true );
			$room_id = get_post_meta( $showtime_id, '_mbs_room_id', true );

			$movie = get_post( $movie_id );
			$cinema = get_post( $cinema_id );
			$room = self::get_room( $room_id );

			$order->movie_title   = $movie ? $movie->post_title : '';
			$order->cinema_name   = $cinema ? $cinema->post_title : '';
			$order->cinema_address= $cinema ? get_post_meta( $cinema->ID, '_mbs_address', true ) : '';
			$order->room_name     = $room ? $room->room_name : '';
			$order->show_date     = get_post_meta( $showtime_id, '_mbs_show_date', true );
			$order->start_time    = get_post_meta( $showtime_id, '_mbs_start_time', true );
			$order->format        = get_post_meta( $showtime_id, '_mbs_format', true );
		}

		return $order;
	}

	/**
	 * Check-in Ticket by QR Hash or Ticket Code
	 */
	public static function checkin_ticket( $code_or_hash, $staff_user_id = 0 ) {
		global $wpdb;
		$table_tickets = $wpdb->prefix . 'booking_tickets';
		$table_orders  = $wpdb->prefix . 'booking_orders';

		$ticket = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_tickets WHERE qr_code_hash = %s OR ticket_code = %s",
			$code_or_hash,
			$code_or_hash
		) );

		if ( ! $ticket ) {
			// Fallback: check if the user entered an Order Code
			$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_orders WHERE order_code = %s", $code_or_hash ) );
			if ( $order ) {
				// Find first unchecked ticket in this order
				$ticket = $wpdb->get_row( $wpdb->prepare(
					"SELECT * FROM $table_tickets WHERE order_id = %d AND checkin_status != 'checked_in' ORDER BY id ASC LIMIT 1",
					$order->id
				) );

				if ( ! $ticket ) {
					// All tickets in this order are already checked in
					$any_ticket = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM $table_tickets WHERE order_id = %d ORDER BY id ASC LIMIT 1",
						$order->id
					) );
					if ( $any_ticket && $any_ticket->checkin_status === 'checked_in' ) {
						return new WP_Error( 'already_checked_in', sprintf(
							__( 'Tất cả vé trong đơn %s đã được soát lúc: %s', 'movie-booking-system' ),
							$order->order_code,
							date( 'd/m/Y H:i:s', strtotime( $any_ticket->checkin_time ) )
						) );
					}
				}
			}
		}

		if ( ! $ticket ) {
			return new WP_Error( 'not_found', __( 'Mã vé hoặc mã đơn không tồn tại trên hệ thống!', 'movie-booking-system' ) );
		}

		if ( $ticket->checkin_status === 'checked_in' ) {
			return new WP_Error( 'already_checked_in', sprintf(
				__( 'Vé này (Ghế %s) đã được soát lúc: %s', 'movie-booking-system' ),
				$ticket->seat_label,
				date( 'd/m/Y H:i:s', strtotime( $ticket->checkin_time ) )
			) );
		}

		$now = current_time( 'mysql' );
		$updated = $wpdb->update(
			$table_tickets,
			array(
				'checkin_status' => 'checked_in',
				'checkin_time'   => $now,
				'checkin_by'     => absint( $staff_user_id ),
			),
			array( 'id' => $ticket->id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'update_failed', __( 'Không thể cập nhật trạng thái vé.', 'movie-booking-system' ) );
		}

		// Enrich ticket details for display
		$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_orders WHERE id = %d", $ticket->order_id ) );
		$movie_id = get_post_meta( $ticket->showtime_id, '_mbs_movie_id', true );
		$cinema_id = get_post_meta( $ticket->showtime_id, '_mbs_cinema_id', true );
		$room_id = get_post_meta( $ticket->showtime_id, '_mbs_room_id', true );

		$movie = get_post( $movie_id );
		$cinema = get_post( $cinema_id );
		$room = self::get_room( $room_id );

		return array(
			'success'        => true,
			'ticket_id'      => $ticket->id,
			'ticket_code'    => $ticket->ticket_code,
			'seat_label'     => $ticket->seat_label,
			'seat_type'      => strtoupper( $ticket->seat_type ),
			'customer_name'  => $order ? $order->customer_name : '',
			'customer_phone' => $order ? $order->customer_phone : '',
			'movie_title'    => $movie ? $movie->post_title : '',
			'cinema_name'    => $cinema ? $cinema->post_title : '',
			'room_name'      => $room ? $room->room_name : '',
			'show_date'      => get_post_meta( $ticket->showtime_id, '_mbs_show_date', true ),
			'start_time'     => get_post_meta( $ticket->showtime_id, '_mbs_start_time', true ),
			'checkin_time'   => date( 'H:i:s d/m/Y', strtotime( $now ) ),
		);
	}

	/**
	 * Coupon Validation
	 */
	public static function validate_coupon( $code, $order_amount ) {
		global $wpdb;
		$table = $wpdb->prefix . 'booking_coupons';
		$coupon = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE code = %s AND status = 'active'", $code ) );

		if ( ! $coupon ) {
			return new WP_Error( 'invalid_coupon', __( 'Mã giảm giá không tồn tại hoặc đã hết hạn.', 'movie-booking-system' ) );
		}

		if ( $coupon->expiry_date && strtotime( $coupon->expiry_date ) < strtotime( 'today' ) ) {
			return new WP_Error( 'coupon_expired', __( 'Mã giảm giá đã hết thời hạn áp dụng.', 'movie-booking-system' ) );
		}

		if ( $coupon->used_count >= $coupon->usage_limit ) {
			return new WP_Error( 'coupon_limit_reached', __( 'Mã giảm giá đã đạt giới hạn lượt sử dụng.', 'movie-booking-system' ) );
		}

		if ( $order_amount < (float) $coupon->min_order_amount ) {
			return new WP_Error( 'min_amount_not_met', sprintf(
				__( 'Đơn hàng cần tối thiểu %s để áp dụng mã này.', 'movie-booking-system' ),
				number_format( $coupon->min_order_amount, 0, ',', '.' ) . 'đ'
			) );
		}

		$discount = 0;
		if ( $coupon->discount_type === 'percentage' ) {
			$discount = ( $order_amount * (float) $coupon->discount_value ) / 100;
		} else {
			$discount = (float) $coupon->discount_value;
		}

		$discount = min( $discount, $order_amount );

		return array(
			'code'           => $coupon->code,
			'discount_type'  => $coupon->discount_type,
			'discount_value' => (float) $coupon->discount_value,
			'discount_amount'=> $discount,
		);
	}
}
