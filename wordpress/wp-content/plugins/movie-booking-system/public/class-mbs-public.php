<?php
/**
 * Frontend Public Enqueue & Setup
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Public {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue Frontend Scripts & Styles
	 */
	public static function enqueue_frontend_assets() {
		// Google Fonts: Outfit & Inter
		wp_enqueue_style( 'mbs-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap', array(), null );

		// Font Awesome 6
		wp_enqueue_style( 'mbs-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );

		$css_path = plugin_dir_path( dirname( __FILE__ ) ) . 'public/css/public-style.css';
		$js_path  = plugin_dir_path( dirname( __FILE__ ) ) . 'public/js/public-app.js';
		$css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : time();
		$js_ver   = file_exists( $js_path ) ? filemtime( $js_path ) : time();

		// Public CSS
		wp_enqueue_style( 'mbs-public-css', plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/public-style.css', array( 'mbs-font-awesome' ), $css_ver );

		// html5-qrcode for checkin scanner
		wp_enqueue_script( 'mbs-html5-qrcode', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js', array(), '2.3.8', true );

		// Public App JS
		wp_enqueue_script( 'mbs-public-app', plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/public-app.js', array( 'jquery' ), $js_ver, true );

		// QR Scanner JS for staff page
		wp_enqueue_script( 'mbs-public-scanner', plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/qr-scanner.js', array( 'jquery', 'mbs-html5-qrcode', 'mbs-public-app' ), $js_ver, true );

		// Generate or retrieve visitor session token
		if ( ! isset( $_COOKIE['mbs_session_token'] ) ) {
			$session_token = wp_generate_password( 32, false, false );
			setcookie( 'mbs_session_token', $session_token, time() + 86400 * 7, COOKIEPATH, COOKIE_DOMAIN );
		} else {
			$session_token = sanitize_text_field( $_COOKIE['mbs_session_token'] );
		}

		wp_localize_script( 'mbs-public-app', 'mbsData', array(
			'restUrl'      => esc_url_raw( rest_url( 'movie-booking/v1/' ) ),
			'restNonce'    => wp_create_nonce( 'wp_rest' ),
			'sessionToken' => $session_token,
			'isLoggedIn'   => is_user_logged_in(),
			'userId'       => get_current_user_id(),
			'homeUrl'      => esc_url( home_url() ),
		) );
	}
}
