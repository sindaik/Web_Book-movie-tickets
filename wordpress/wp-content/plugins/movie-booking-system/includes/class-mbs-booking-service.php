<?php
/**
 * Booking Business Logic Service
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Booking_Service {

	/**
	 * Available Concession Combos
	 */
	public static function get_concession_combos() {
		return array(
			array(
				'id'          => 'combo_solo',
				'name'        => 'Combo Solo',
				'desc'        => '1 Bắp Ngọt 60oz + 1 Nước Có Ga 22oz',
				'price'       => 75000,
				'image'       => 'popcorn-solo.png',
			),
			array(
				'id'          => 'combo_couple',
				'name'        => 'Combo Sweet Couple',
				'desc'        => '1 Bắp Phô Mai / Caramel Lớn + 2 Nước Có Ga 22oz',
				'price'       => 109000,
				'image'       => 'popcorn-couple.png',
			),
			array(
				'id'          => 'combo_party',
				'name'        => 'Combo Big Party',
				'desc'        => '2 Bắp Lớn (tùy chọn vị) + 4 Nước + 1 Snack Khoai Tây',
				'price'       => 189000,
				'image'       => 'popcorn-party.png',
			),
			array(
				'id'          => 'combo_family',
				'name'        => 'Combo Family VIP',
				'desc'        => '2 Bắp Khổng Lồ Phô Mai + 4 Nước Lớn + 2 Xúc Xích Nướng Phô Mai',
				'price'       => 249000,
				'image'       => 'popcorn-family.png',
			),
		);
	}

	/**
	 * Process Complete Booking Checkout
	 */
	public static function process_checkout( $params ) {
		// 1. Sanitize & Validate Inputs
		$customer_name  = sanitize_text_field( $params['customer_name'] ?? '' );
		$customer_email = sanitize_email( $params['customer_email'] ?? '' );
		$customer_phone = sanitize_text_field( $params['customer_phone'] ?? '' );
		$showtime_id    = absint( $params['showtime_id'] ?? 0 );
		$seat_ids       = array_map( 'absint', (array) ( $params['seat_ids'] ?? array() ) );
		$session_token  = sanitize_text_field( $params['session_token'] ?? '' );
		$payment_method = sanitize_text_field( $params['payment_method'] ?? 'sandbox' );
		$coupon_code    = sanitize_text_field( $params['coupon_code'] ?? '' );
		$combos_input   = (array) ( $params['combos'] ?? array() );

		if ( empty( $customer_name ) || empty( $customer_email ) || empty( $customer_phone ) ) {
			return new WP_Error( 'missing_info', __( 'Vui lòng nhập đầy đủ họ tên, email và số điện thoại.', 'movie-booking-system' ) );
		}

		if ( ! is_email( $customer_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Địa chỉ email không hợp lệ.', 'movie-booking-system' ) );
		}

		if ( empty( $showtime_id ) || empty( $seat_ids ) ) {
			return new WP_Error( 'empty_seats', __( 'Vui lòng chọn ít nhất một ghế.', 'movie-booking-system' ) );
		}

		// 2. Fetch Showtime and Seat Details to calculate verified prices
		$base_price = (float) get_post_meta( $showtime_id, '_mbs_base_price', true ) ?: 95000;
		$room_id    = absint( get_post_meta( $showtime_id, '_mbs_room_id', true ) );
		$all_seats  = MBS_DB::get_seats_by_room( $room_id );

		$seats_indexed = array();
		foreach ( $all_seats as $s ) {
			$seats_indexed[ $s->id ] = $s;
		}

		$tickets_total = 0;
		$tickets_data = array();

		foreach ( $seat_ids as $sid ) {
			if ( ! isset( $seats_indexed[ $sid ] ) ) {
				return new WP_Error( 'invalid_seat_id', __( 'Ghế đã chọn không tồn tại trong phòng chiếu.', 'movie-booking-system' ) );
			}

			$seat = $seats_indexed[ $sid ];
			$price = $base_price + (float) $seat->seat_price_modifier;
			$tickets_total += $price;

			$ticket_code = 'TK' . date( 'ymd' ) . strtoupper( wp_generate_password( 6, false, false ) );
			$qr_hash     = hash( 'sha256', $ticket_code . AUTH_KEY . $showtime_id . $sid );

			$tickets_data[] = array(
				'showtime_id'  => $showtime_id,
				'seat_id'      => $sid,
				'seat_label'   => $seat->seat_row . sprintf( '%02d', $seat->seat_number ),
				'seat_type'    => $seat->seat_type,
				'price'        => $price,
				'ticket_code'  => $ticket_code,
				'qr_code_hash' => $qr_hash,
			);
		}

		// 3. Calculate Combos
		$combos_catalog = self::get_concession_combos();
		$combos_indexed = array();
		foreach ( $combos_catalog as $c ) {
			$combos_indexed[ $c['id'] ] = $c;
		}

		$combos_total = 0;
		$selected_combos = array();
		foreach ( $combos_input as $cid => $qty ) {
			$qty = absint( $qty );
			if ( $qty > 0 && isset( $combos_indexed[ $cid ] ) ) {
				$item = $combos_indexed[ $cid ];
				$cost = $qty * $item['price'];
				$combos_total += $cost;
				$selected_combos[] = array(
					'id'       => $cid,
					'name'     => $item['name'],
					'qty'      => $qty,
					'price'    => $item['price'],
					'subtotal' => $cost,
				);
			}
		}

		$subtotal = $tickets_total + $combos_total;

		// 4. Validate and Apply Coupon
		$discount_amount = 0;
		if ( ! empty( $coupon_code ) ) {
			$coupon_res = MBS_DB::validate_coupon( $coupon_code, $subtotal );
			if ( ! is_wp_error( $coupon_res ) ) {
				$discount_amount = $coupon_res['discount_amount'];
			}
		}

		$final_amount = max( 0, $subtotal - $discount_amount );
		$order_code = 'MBS' . date( 'ymd' ) . '-' . strtoupper( wp_generate_password( 5, false, false ) );
		$user_id = get_current_user_id();

		// Auto-link to registered member if user_id is 0
		if ( empty( $user_id ) && ! empty( $customer_email ) ) {
			$found_user = get_user_by( 'email', $customer_email );
			if ( $found_user ) {
				$user_id = $found_user->ID;
			}
		}
		if ( empty( $user_id ) && ! empty( $customer_phone ) ) {
			$found_users = get_users( array(
				'meta_key'   => 'billing_phone',
				'meta_value' => $customer_phone,
				'number'     => 1,
			) );
			if ( ! empty( $found_users ) ) {
				$user_id = $found_users[0]->ID;
			}
		}

		// 5. Prepare Order Data
		$order_data = array(
			'order_code'      => $order_code,
			'user_id'         => $user_id,
			'customer_name'   => $customer_name,
			'customer_email'  => $customer_email,
			'customer_phone'  => $customer_phone,
			'total_amount'    => $subtotal,
			'discount_amount' => $discount_amount,
			'final_amount'    => $final_amount,
			'combos'          => $selected_combos,
			'coupon_code'     => $coupon_code,
			'payment_method'  => $payment_method,
			'payment_status'  => ( $payment_method === 'vnpay' ) ? 'pending' : 'paid',
			'transaction_id'  => 'TXN-' . time(),
			'session_token'   => $session_token,
		);

		// 6. DB Transaction: Insert Order & Tickets
		$result = MBS_DB::create_order( $order_data, $tickets_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// 7. Payment Gateway Dispatch
		$payment_res = MBS_Payment::process_payment( $order_data );

		// 8. Fetch complete order object and dispatch email confirmation
		$created_order = MBS_DB::get_order_by_code( $order_code );
		if ( $created_order && $payment_res['status'] !== 'redirect' ) {
			MBS_Mailer::send_booking_confirmation( $created_order );
		}

		return array(
			'success'        => true,
			'order_code'     => $order_code,
			'order_id'       => $result['order_id'],
			'final_amount'   => $final_amount,
			'payment_status' => $payment_res['status'],
			'payment_method' => $payment_method,
			'redirect_url'   => $payment_res['redirect_url'] ?? '',
			'qr_data'        => $payment_res['qr_data'] ?? '',
			'tickets'        => $tickets_data,
		);
	}

	/**
	 * Cancel Order / Ticket (Allowed if showtime starts > 120 mins away)
	 */
	public static function cancel_booking( $order_code, $user_id ) {
		global $wpdb;
		$order = MBS_DB::get_order_by_code( $order_code );

		if ( ! $order ) {
			return new WP_Error( 'not_found', __( 'Không tìm thấy đơn đặt vé.', 'movie-booking-system' ) );
		}

		if ( absint( $order->user_id ) !== absint( $user_id ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'unauthorized', __( 'Bạn không có quyền thao tác trên đơn hàng này.', 'movie-booking-system' ) );
		}

		if ( $order->payment_status === 'cancelled' ) {
			return new WP_Error( 'already_cancelled', __( 'Đơn hàng này đã được hủy trước đó.', 'movie-booking-system' ) );
		}

		// Check cancellation window
		$settings = get_option( 'mbs_settings', array() );
		$cancel_mins = $settings['cancel_allowed_before'] ?? 120;

		$showtime_datetime = $order->show_date . ' ' . $order->start_time;
		$diff_seconds = strtotime( $showtime_datetime ) - current_time( 'timestamp' );

		if ( $diff_seconds < ( $cancel_mins * 60 ) ) {
			return new WP_Error( 'cancel_window_closed', sprintf(
				__( 'Rất tiếc! Chỉ được phép hoàn/hủy vé trước giờ chiếu tối thiểu %d phút.', 'movie-booking-system' ),
				$cancel_mins
			) );
		}

		// Update order & delete tickets
		$table_orders  = $wpdb->prefix . 'booking_orders';
		$table_tickets = $wpdb->prefix . 'booking_tickets';

		$wpdb->update(
			$table_orders,
			array( 'payment_status' => 'cancelled' ),
			array( 'id' => $order->id ),
			array( '%s' ),
			array( '%d' )
		);

		$wpdb->delete( $table_tickets, array( 'order_id' => $order->id ), array( '%d' ) );

		return array(
			'success' => true,
			'message' => __( 'Hủy vé thành công. Tiền sẽ được hoàn lại theo quy định của rạp.', 'movie-booking-system' ),
		);
	}
}
