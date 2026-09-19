<?php
/**
 * Ticket Confirmation Email & SMS Notification Handler
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Mailer {

	/**
	 * Send Ticket Confirmation Email & SMS
	 */
	public static function send_booking_confirmation( $order ) {
		if ( empty( $order ) || empty( $order->customer_email ) ) {
			return false;
		}

		$settings = get_option( 'mbs_settings', array() );
		$sender_name    = $settings['email_sender_name'] ?? get_bloginfo( 'name' );
		$sender_address = $settings['email_sender_address'] ?? get_option( 'admin_email' );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			"From: $sender_name <$sender_address>",
		);

		$subject = sprintf( '[%s] Xác Nhận Vé Xem Phim - Mã Đơn: %s', $sender_name, $order->order_code );
		$message = self::get_email_template( $order );

		$email_sent = wp_mail( $order->customer_email, $subject, $message, $headers );

		// Simulate SMS Sending
		self::send_sms_notification( $order );

		return $email_sent;
	}

	/**
	 * Build Responsive HTML Email Template
	 */
	public static function get_email_template( $order ) {
		$seats_str = '';
		if ( ! empty( $order->tickets ) ) {
			$seat_labels = array_map( function( $t ) {
				return $t->seat_label . ' (' . strtoupper( $t->seat_type ) . ')';
			}, $order->tickets );
			$seats_str = implode( ', ', $seat_labels );
		}

		$qr_first_ticket = ! empty( $order->tickets[0] ) ? $order->tickets[0]->ticket_code : $order->order_code;
		$qr_image_url = MBS_QR_Code::get_qr_image_url( $qr_first_ticket, 180 );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<style>
				body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0b0f19; color: #f8fafc; margin: 0; padding: 20px; }
				.ticket-card { max-width: 600px; margin: 0 auto; background: #161f30; border-radius: 16px; overflow: hidden; border: 1px solid #2d3748; }
				.header { background: linear-gradient(135deg, #e50914, #b20710); padding: 24px; text-align: center; color: #fff; }
				.header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
				.body { padding: 24px; }
				.movie-title { font-size: 20px; font-weight: bold; color: #ffb800; margin-bottom: 12px; }
				.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #2d3748; font-size: 14px; }
				.info-label { color: #94a3b8; }
				.info-val { font-weight: 600; color: #ffffff; }
				.qr-box { text-align: center; margin: 24px 0 10px; padding: 16px; background: #0b0f19; border-radius: 12px; }
				.qr-box img { border-radius: 8px; background: #ffffff; padding: 6px; }
				.footer { text-align: center; font-size: 12px; color: #64748b; padding: 16px; background: #0f172a; }
			</style>
		</head>
		<body>
			<div class="ticket-card">
				<div class="header">
					<h1>Xác Nhận Đặt Vé Thành Công</h1>
					<p style="margin: 5px 0 0; opacity: 0.9;">Cảm ơn bạn đã lựa chọn rạp của chúng tôi!</p>
				</div>
				<div class="body">
					<div class="movie-title"><?php echo esc_html( $order->movie_title ?? 'Phim Chiếu Rạp' ); ?></div>
					<div class="info-row">
						<span class="info-label">Mã Đơn Hàng:</span>
						<span class="info-val" style="color: #38bdf8;"><?php echo esc_html( $order->order_code ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label">Khách Hàng:</span>
						<span class="info-val"><?php echo esc_html( $order->customer_name ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label">Rạp Chiếu:</span>
						<span class="info-val"><?php echo esc_html( $order->cinema_name ?? '' ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label">Phòng Chiếu:</span>
						<span class="info-val"><?php echo esc_html( $order->room_name ?? '' ); ?> (<?php echo esc_html( $order->format ?? '2D' ); ?>)</span>
					</div>
					<div class="info-row">
						<span class="info-label">Suất Chiếu:</span>
						<span class="info-val" style="color: #4ade80;"><?php echo esc_html( ( $order->start_time ?? '' ) . ' - ' . date( 'd/m/Y', strtotime( $order->show_date ?? 'today' ) ) ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label">Vị Trí Ghế:</span>
						<span class="info-val" style="color: #ffb800; font-size: 16px;"><?php echo esc_html( $seats_str ); ?></span>
					</div>
					<div class="info-row">
						<span class="info-label">Tổng Thanh Toán:</span>
						<span class="info-val"><?php echo esc_html( number_format( $order->final_amount, 0, ',', '.' ) . 'đ' ); ?></span>
					</div>

					<div class="qr-box">
						<p style="margin-top:0; color:#cbd5e1; font-size:13px;">Đưa mã QR này cho nhân viên tại quầy soát vé:</p>
						<img src="<?php echo esc_url( $qr_image_url ); ?>" width="160" height="160" alt="Ticket QR Code" />
						<p style="margin:8px 0 0; font-family: monospace; font-size: 14px; letter-spacing: 2px; color: #38bdf8;"><?php echo esc_html( $qr_first_ticket ); ?></p>
					</div>
				</div>
				<div class="footer">
					<p style="margin: 0;">Vui lòng có mặt tại rạp trước giờ chiếu ít nhất 15 phút. Chúc bạn xem phim vui vẻ!</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send SMS Notification (Simulation / API Logger)
	 */
	public static function send_sms_notification( $order ) {
		if ( empty( $order->customer_phone ) ) {
			return;
		}

		$sms_text = sprintf(
			'CINEMA: Ma ve %s cho phim %s luc %s %s tai %s. Ghe: %s. Chuc ban xem phim vui ve!',
			$order->order_code,
			$order->movie_title ?? 'Phim',
			$order->start_time ?? '',
			$order->show_date ?? '',
			$order->cinema_name ?? 'Rap',
			! empty( $order->tickets[0] ) ? $order->tickets[0]->seat_label : ''
		);

		// Store in transient/log for demonstration and testing verification
		$sms_logs = get_option( 'mbs_sms_logs', array() );
		array_unshift( $sms_logs, array(
			'phone'     => $order->customer_phone,
			'message'   => $sms_text,
			'sent_at'   => current_time( 'mysql' ),
		) );
		if ( count( $sms_logs ) > 50 ) {
			$sms_logs = array_slice( $sms_logs, 0, 50 );
		}
		update_option( 'mbs_sms_logs', $sms_logs );
	}
}
