<?php
/**
 * Plugin Name:       Movie Booking System (Hệ Thống Đặt Vé Xem Phim)
 * Plugin URI:        https://cinemabooking.local/
 * Description:       Hệ thống đặt vé xem phim trực tuyến toàn diện: Quản lý phim, rạp & phòng chiếu, sơ đồ ghế thời gian thực (chống double-booking), combo bắp nước, mã giảm giá, cổng thanh toán VNPay/MoMo Sandbox, email vé QR Code và ứng dụng soát vé bằng camera.
 * Version:           1.0.0
 * Author:            Antigravity Team
 * Author URI:        https://cinemabooking.local/
 * Text Domain:       movie-booking-system
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants
define( 'MBS_VERSION', '1.0.0' );
define( 'MBS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload / Require Core Files
 */
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-activator.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-deactivator.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-qr.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-cpt.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-db.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-payment.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-mailer.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-booking-service.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-rest-api.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-seeder.php';
require_once MBS_PLUGIN_DIR . 'includes/class-mbs-shortcodes.php';

// Admin Files
if ( is_admin() ) {
	require_once MBS_PLUGIN_DIR . 'admin/class-mbs-admin.php';
}

// Public Files
require_once MBS_PLUGIN_DIR . 'public/class-mbs-public.php';

/**
 * Activation & Deactivation Hooks
 */
register_activation_hook( __FILE__, array( 'MBS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MBS_Deactivator', 'deactivate' ) );

/**
 * Initialize Plugin Components
 */
function mbs_init_plugin() {
	// Initialize CPT
	MBS_CPT::init();

	// Initialize REST API
	MBS_REST_API::init();

	// Initialize Shortcodes
	MBS_Shortcodes::init();

	// Initialize Public Assets
	MBS_Public::init();

	// Initialize Admin Components
	if ( is_admin() ) {
		MBS_Admin::init();
	}

	// Auto-seed demo data on first load if not seeded yet
	if ( ! get_option( 'mbs_demo_data_seeded' ) ) {
		MBS_Seeder::run();
	}
}
add_action( 'plugins_loaded', 'mbs_init_plugin' );
