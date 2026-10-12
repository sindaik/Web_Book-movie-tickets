<?php
/**
 * The base configuration for WordPress
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1:3307' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 */
define( 'AUTH_KEY',         'mbs_cin_k93#8kd01kd9a8d9a29kdf9ak39fk39' );
define( 'SECURE_AUTH_KEY',  'mbs_cin_8dk2kd940kfa938dfk39d8fk39dkf3a' );
define( 'LOGGED_IN_KEY',    'mbs_cin_38dk92kd9a8dfk39dkf39dkf9a39dfk' );
define( 'NONCE_KEY',        'mbs_cin_83kd92kd9akf39dkf9akf39dkf39dkf' );
define( 'AUTH_SALT',        'mbs_cin_18dk39dkf9ak39dkf9ak39dkf39dkf3' );
define( 'SECURE_AUTH_SALT', 'mbs_cin_84kd92kd9akf39dkf9akf39dkf39dkf2' );
define( 'LOGGED_IN_SALT',   'mbs_cin_93kd92kd9akf39dkf9akf39dkf39dkf1' );
define( 'NONCE_SALT',       'mbs_cin_72kd92kd9akf39dkf9akf39dkf39dkf0' );

/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 */
define( 'WP_DEBUG', false );
define( 'FS_METHOD', 'direct' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
