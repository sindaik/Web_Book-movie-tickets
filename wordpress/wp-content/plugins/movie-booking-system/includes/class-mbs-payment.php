<?php
/**
 * Payment Gateways Integration (VNPay Sandbox, MoMo Sandbox, Instant Test Pay)
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Payment {

	/**
	 * Process Payment Gateway Redirection or Mock Response
	 *
	 * @param array $order Order data
	 * @return array Payment URL and status
	 */
	public static function process_payment( $order ) {
		$method = sanitize_text_field( $order['payment_method'] ?? 'sandbox' );
		$settings = get_option( 'mbs_settings', array() );

		switch ( $method ) {
			case 'vnpay':
				return self::create_vnpay_url( $order, $settings );

			case 'momo':
				return self::create_momo_payload( $order, $settings );

			case 'sandbox':
			default:
				return array(
					'success'      => true,
					'method'       => 'sandbox',
					'status'       => 'paid',
					'redirect_url' => '',
					'message'      => __( 'Thanh toán thành công qua Sandbox!', 'movie-booking-system' ),
				);
		}
	}

	/**
	 * Generate VNPay Sandbox Redirect URL
	 */
	private static function create_vnpay_url( $order, $settings ) {
		$vnp_TmnCode    = $settings['vnpay_tmn_code'] ?? 'DEMOVNPAY';
		$vnp_HashSecret = $settings['vnpay_hash_secret'] ?? 'RAOSecretKey123456789DemoVNPaySandbox';
		$vnp_Url        = $settings['vnpay_url'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
		$vnp_Returnurl  = home_url( '/dat-ve/?payment_return=vnpay&order=' . $order['order_code'] );

		$vnp_TxnRef     = $order['order_code'];
		$vnp_OrderInfo  = 'Thanh toan ve xem phim: ' . $order['order_code'];
		$vnp_OrderType  = 'billpayment';
		$vnp_Amount     = $order['final_amount'] * 100; // VNPay requires x100
		$vnp_Locale     = 'vn';
		$vnp_IpAddr     = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
		$vnp_CreateDate = date( 'YmdHis' );

		$inputData = array(
			'vnp_Version'        => '2.1.0',
			'vnp_TmnCode'        => $vnp_TmnCode,
			'vnp_Amount'         => $vnp_Amount,
			'vnp_Command'        => 'pay',
			'vnp_CreateDate'     => $vnp_CreateDate,
			'vnp_CurrCode'       => 'VND',
			'vnp_IpAddr'         => $vnp_IpAddr,
			'vnp_Locale'         => $vnp_Locale,
			'vnp_OrderInfo'      => $vnp_OrderInfo,
			'vnp_OrderType'      => $vnp_OrderType,
			'vnp_ReturnUrl'      => $vnp_Returnurl,
			'vnp_TxnRef'         => $vnp_TxnRef,
		);

		ksort( $inputData );
		$query = '';
		$i = 0;
		$hashdata = '';
		foreach ( $inputData as $key => $value ) {
			if ( $i == 1 ) {
				$hashdata .= '&' . urlencode( $key ) . '=' . urlencode( $value );
			} else {
				$hashdata .= urlencode( $key ) . '=' . urlencode( $value );
				$i = 1;
			}
			$query .= urlencode( $key ) . '=' . urlencode( $value ) . '&';
		}

		$vnp_Url = $vnp_Url . '?' . $query;
		if ( isset( $vnp_HashSecret ) ) {
			$vnpSecureHash = hash_hmac( 'sha512', $hashdata, $vnp_HashSecret );
			$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
		}

		return array(
			'success'      => true,
			'method'       => 'vnpay',
			'status'       => 'redirect',
			'redirect_url' => $vnp_Url,
			'message'      => __( 'Đang chuyển hướng tới cổng VNPay...', 'movie-booking-system' ),
		);
	}

	/**
	 * Generate MoMo Sandbox QR & Callback Payload
	 */
	private static function create_momo_payload( $order, $settings ) {
		$partnerCode = $settings['momo_partner_code'] ?? 'MOMODEMO123';
		$orderId = $order['order_code'];
		$amount = (string) $order['final_amount'];

		// MoMo Deep Link & QR Data
		$qr_content = sprintf( '2|99|%s|%s|%s|0|0|%s|momo_transfer', $partnerCode, $order['customer_phone'], $order['customer_name'], $amount );

		return array(
			'success'      => true,
			'method'       => 'momo',
			'status'       => 'qr_modal',
			'qr_data'      => $qr_content,
			'redirect_url' => '',
			'message'      => __( 'Vui lòng quét mã QR MoMo để thanh toán.', 'movie-booking-system' ),
		);
	}
}
