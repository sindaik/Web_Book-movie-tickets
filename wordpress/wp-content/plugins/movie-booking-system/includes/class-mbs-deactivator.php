<?php
/**
 * Fired during plugin deactivation
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Deactivator {

	/**
	 * Run deactivator tasks
	 */
	public static function deactivate() {
		// Clear transients / locks
		global $wpdb;
		$table_locks = $wpdb->prefix . 'booking_seat_locks';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_locks'" ) === $table_locks ) {
			$wpdb->query( "DELETE FROM $table_locks WHERE expires_at < NOW()" );
		}

		flush_rewrite_rules();
	}
}
